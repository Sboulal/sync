<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
// 🟢 Package ديال الكود بار
use Picqer\Barcode\BarcodeGeneratorPNG;

class AppelFondsController extends Controller
{
    // ========================================================
    // 🟢 FONCTION SÉCURISÉE AVEC AUTHENTIFICATION RÉELLE
    // ========================================================
    private function getProprieteId(Request $request)
    {
        $userId = auth()->id(); 
        if (!$userId) return null; 

        $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        // 🟢 كنقراو residence_id من (Headers -> Body -> Cookie) بحال الباقي
        $residenceId = $request->header('residence_id') ?? $request->input('residence_id') ?? $request->cookie('residence_id');

        if ($residenceId) {
            $link = DB::table('user_as_owner')
                ->where('user_id', (string)$userId)
                ->where($propOwnerCol, $residenceId)
                ->first();
            if ($link) return $residenceId;
        }

        // 2. إيلا مالقاش، عاد كيجيب أول إقامة كاحتياط
        $link = DB::table('user_as_owner')
            ->where('user_id', (string)$userId)
            ->first();
        
        return $link ? $link->$propOwnerCol : null;
    }

    public function liste(Request $request) {
        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $propIdCol_propriete = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
        $residence = DB::table('proprietes')->where($propIdCol_propriete, $sp_id)->first();

        $se_id = $request->se_identifier; 
        $type = $request->type_charge; 

        if (!$se_id) {
            $exercice = DB::table('exercices')
                ->where('propriete_id', $sp_id) 
                ->where('status', 'en cours') 
                ->first();
            
            if (!$exercice) {
                $exercice = DB::table('exercices')
                    ->where('propriete_id', $sp_id) 
                    ->orderBy('start_date', 'desc')
                    ->first();
            }
            
            if (!$exercice) return response()->json([
                'success' => true, 
                'residence' => [
                    'nom' => $residence->nom ?? 'Résidence',
                    'adresse' => $residence->address ?? 'Adresse non définie'
                ],
                'data' => [], 
                'exercice' => null
            ]);
            $se_id = $exercice->se_identifier;
        } else {
            $exercice = DB::table('exercices')->where('se_identifier', $se_id)->first();
        }

        $appels = DB::table('appels_fonds')
            ->where('se_identifier', $se_id)
            ->where('type_charge', $type)
            ->orderBy('due_date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'residence' => [
                'nom' => $residence->nom ?? 'Résidence',
                'adresse' => $residence->address ?? 'Adresse non définie'
            ],
            'data' => $appels,
            'exercice' => $exercice
        ]);
    }

    // ==========================================
    // 2. AJOUTER UN APPEL DE FONDS - PLANIFIÉ
    // ==========================================
    public function ajouterPlanifie(Request $request)
    {
        Log::info('--- DÉBUT : Ajout AF Planifié ---');
        $request->validate([
            'se_identifier' => 'required|string',
            'type_charge' => 'required|in:previsionnel,travaux'
        ]);

        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $se_id = $request->se_identifier;
        $type = $request->type_charge;

        DB::beginTransaction();
        try {
            $exercice = DB::table('exercices')->where('se_identifier', $se_id)->first();
            if (!$exercice || !in_array($exercice->status, ['en cours', 'en attente'])) {
                return response()->json(['success' => false, 'message' => "L'exercice n'est pas actif."], 400);
            }

            $tableBudget = $type === 'previsionnel' ? 'charges_previsionnelles' : 'charges_travaux';
            $budget = DB::table($tableBudget)->where('se_identifier', $se_id)->first();

            if (!$budget || $budget->budget <= 0) {
                return response()->json(['success' => false, 'message' => "Impossible de créer un appel planifié pour un budget de 0 DH."], 400);
            }

            $existants = DB::table('appels_fonds')
                ->where('se_identifier', $se_id)
                ->where('type_charge', $type)
                ->where('sub_type', 'planifie')
                ->get();

            if ($existants->count() > 0) {
                $dejaUtilise = $existants->where('is_generated', 1)->count() > 0;
                if ($dejaUtilise) {
                    return response()->json(['success' => false, 'message' => "Impossible d'actualiser : certains appels sont déjà générés ou envoyés."], 400);
                }
                
                DB::table('appels_fonds')
                    ->where('se_identifier', $se_id)
                    ->where('type_charge', $type)
                    ->where('sub_type', 'planifie')
                    ->delete();
            }

            $periode = strtolower($exercice->period ?? 'trimestre');
            $nbPeriodes = $periode === 'mensuel' ? 12 : ($periode === 'quadrimestre' ? 3 : 4);
            $montantParPeriode = $budget->budget / $nbPeriodes;
            $startDate = Carbon::parse($exercice->start_date);
            $endDate = Carbon::parse($exercice->end_date);
            $moisAajouter = 12 / $nbPeriodes; 
            
            $appelsCrees = [];

            for ($i = 0; $i < $nbPeriodes; $i++) {
                $dateExigibilite = $startDate->copy()->addMonths($i * $moisAajouter);
                if ($dateExigibilite->greaterThan($endDate)) $dateExigibilite = $endDate; 

                $af_id = 'AF-PL-' . time() . "-$i";
                $nouveau = [
                    'af_identifier' => $af_id,
                    'se_identifier' => $se_id,
                    'type_charge' => $type,
                    'sub_type' => 'planifie',
                    'title' => "Appel planifié ($periode " . ($i+1) . ")",
                    'amount' => $montantParPeriode,
                    'due_date' => $dateExigibilite->format('Y-m-d'),
                    'created_at' => now(), 'updated_at' => now()
                ];
                DB::table('appels_fonds')->insert($nouveau);
                $appelsCrees[] = $nouveau;
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Appels générés/actualisés avec succès.', 'data' => $appelsCrees]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 3. AJOUTER UN APPEL DE FONDS - EXCEPTIONNEL
    // ==========================================
    public function ajouterExceptionnel(Request $request)
    {
        Log::info('--- DÉBUT : Ajout AF Exceptionnel ---');
        $request->validate([
            'se_identifier' => 'required|string',
            'type_charge' => 'required|in:previsionnel,travaux',
            'cle_repartition_id' => 'required', 
            'amount' => 'required|numeric|min:1',
            'due_date' => 'required|date',
            'title' => 'required|string'
        ]);

        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $se_id = $request->se_identifier;
        $type = $request->type_charge;

        DB::beginTransaction();
        try {
            $exercice = DB::table('exercices')->where('se_identifier', $se_id)->first();
            if (!$exercice || !in_array($exercice->status, ['en cours', 'en attente'])) {
                return response()->json(['success' => false, 'message' => "L'exercice n'est pas actif."], 400);
            }

            $af_id = 'AF-EX-' . time();
            $nouveau = [
                'af_identifier' => $af_id,
                'se_identifier' => $se_id,
                'cle_repartition_id' => $request->cle_repartition_id,
                'type_charge' => $type,
                'sub_type' => 'exceptionnel',
                'title' => $request->title,
                'amount' => $request->amount,
                'due_date' => $request->due_date,
                'created_at' => now(), 'updated_at' => now()
            ];
            DB::table('appels_fonds')->insert($nouveau);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Ajout réussi.', 'data' => $nouveau]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

    public function generer(Request $request)
    {
        $request->validate([
            'se_identifier' => 'required|string',
            'af_identifier' => 'required|string'
        ]);

        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        DB::beginTransaction();
        try {
            $appel = DB::table('appels_fonds')
                ->where('af_identifier', $request->af_identifier)
                ->lockForUpdate()
                ->first();

            if (!$appel || (int) $appel->is_generated === 1) {
                return response()->json(['success' => false, 'message' => "Invalide ou déjà généré."], 400);
            }

            if ($appel->sub_type === 'planifie' && Carbon::parse($appel->due_date)->isFuture()) {
                return response()->json([
                    'success' => false,
                    'message' => "Cet appel planifié ne peut pas encore être généré. La date prévue est le "
                        . Carbon::parse($appel->due_date)->locale('fr')->translatedFormat('d F Y') . "."
                ], 400);
            }

            $exercice = DB::table('exercices')->where('se_identifier', $appel->se_identifier)->first();
            if (!$exercice || $exercice->status !== 'en cours') {
                return response()->json(['success' => false, 'message' => "L'exercice doit être 'en cours'."], 400);
            }

            $propIdCol = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
            $residence = DB::table('proprietes')->where($propIdCol, $sp_id)->first();
            $syndic    = auth()->user();
            $authUserId = (string) auth()->id();

            $cleIdCol   = Schema::hasColumn('unit_to_key', 'cle_repartition_id') ? 'cle_repartition_id' : 'key_id';
            $propKeyCol = Schema::hasColumn('cle_repartitions', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

            $cle_id = $appel->cle_repartition_id;

            if (!$cle_id) {
                $defaultCle = DB::table('cle_repartitions')->where($propKeyCol, $sp_id)->first();
                $cle_id = $defaultCle ? $defaultCle->id : null;
            }

            $balanceCol = ($appel->type_charge === 'previsionnel') ? 'balance_prev' : 'balance_trav';
            if (!Schema::hasColumn('user_as_owner', $balanceCol)) {
                $balanceCol = Schema::hasColumn('user_as_owner', 'balance_prev') ? 'balance_prev' : 'solde';
            }

            $queryTotal = DB::table('unit_to_key')
                ->join('units', 'unit_to_key.unit_id', '=', 'units.id')
                ->where('units.propriete_id', $sp_id);

            if ($cle_id) {
                $queryTotal->where('unit_to_key.' . $cleIdCol, $cle_id);
            }

            $totalTantiemes = $queryTotal->sum('unit_to_key.tantieme');

            if ($totalTantiemes <= 0) {
                return response()->json(['success' => false, 'message' => "La somme des tantièmes pour cette clé est 0 ou introuvable."], 400);
            }

            $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

            $queryProprietaires = DB::table('user_as_owner')
                ->where($propOwnerCol, $sp_id)
                ->whereRaw('user_as_owner.user_id::text != ?', [$authUserId]);

            if (Schema::hasColumn('user_as_owner', 'role')) {
                $queryProprietaires->where(function($q) {
                    $q->where('role', '!=', 'syndic')->orWhereNull('role');
                });
            }

            $proprietaires = $queryProprietaires->get();

            if ($proprietaires->isEmpty()) {
                return response()->json(['success' => false, 'message' => "Aucun copropriétaire trouvé."], 400);
            }

            DB::table('appf_to_owner')->where('af_identifier', $appel->af_identifier)->delete();

            $dossierPath   = "proprietes/{$sp_id}/appels_fonds/{$appel->af_identifier}";
            $dossierAbsolu = storage_path("app/public/{$dossierPath}");
            if (!file_exists($dossierAbsolu)) {
                mkdir($dossierAbsolu, 0775, true);
            }

            $fichiersPdf    = [];
            $documentsCrees = 0;

            foreach ($proprietaires as $p) {
                $queryUserTantiemes = DB::table('user_owner_unit')
                    ->join('unit_to_key', 'user_owner_unit.unit_id', '=', 'unit_to_key.unit_id')
                    ->where('user_owner_unit.user_id', $p->user_id);

                if ($cle_id) {
                    $queryUserTantiemes->where('unit_to_key.' . $cleIdCol, $cle_id);
                }

                $userTantiemes = $queryUserTantiemes->sum('unit_to_key.tantieme');
                $montantDu = $totalTantiemes > 0 ? round(($appel->amount * $userTantiemes) / $totalTantiemes, 2) : 0;

                if ($montantDu <= 0) continue;

                $row = DB::table('user_as_owner')
                    ->whereRaw('CAST(user_id AS VARCHAR) = ?', [(string) $p->user_id])
                    ->whereRaw('CAST(' . $propOwnerCol . ' AS VARCHAR) = ?', [$sp_id])
                    ->first();
                $soldeActuel_lors_de_la_generation = $row ? (float) ($row->$balanceCol ?? 0) : 0;

                DB::table('appf_to_owner')->insertOrIgnore([
                    'af_identifier' => $appel->af_identifier,
                    'user_id'       => $p->user_id,
                    'montant_du'    => $montantDu,
                    'solde_avant'   => $soldeActuel_lors_de_la_generation, 
                    'created_at'    => now(),
                ]);

                $userDb   = DB::table('users')->where('id', $p->user_id)->first();
                $userLots = DB::table('user_owner_unit')
                    ->join('units', 'user_owner_unit.unit_id', '=', 'units.id')
                    ->where('user_owner_unit.user_id', $p->user_id)
                    ->get(['units.numero_porte', 'units.type', 'units.batiment'])
                    ->map(function ($unit) {
                        $type = !empty($unit->type) ? $unit->type : 'Bien';
                        $desc = "{$type} {$unit->numero_porte}";
                        if (!empty($unit->batiment)) {
                            $desc .= " (Bât. {$unit->batiment})";
                        }
                        return $desc;
                    })
                    ->filter()
                    ->implode(', ');

                $generator = new BarcodeGeneratorPNG();
                $hashCourt = substr(md5($appel->af_identifier), 0, 8); 
                $barcodeBase64 = base64_encode($generator->getBarcode($hashCourt, $generator::TYPE_CODE_128, 1.5, 35));

                $pdfData = [
                    'reference'   => ucfirst($appel->sub_type) . ' (' . ucfirst($appel->type_charge) . ')',
                    'date_limite' => $appel->due_date,
                    'adresse'     => $residence->address ?? 'Adresse non définie',
                    'telephone'   => $syndic->tel ?? '06 00 00 00 00',
                    'email'       => $syndic->email ?? 'contact@syndic.com',
                    'iban'        => $residence->iban ?? '[IBAN]',
                    'barcode'     => $barcodeBase64, 
                    'lignes'      => [[
                        'nom'        => $userDb->full_name ?? $userDb->email ?? 'Copropriétaire',
                        'montant_du' => $montantDu,
                        'lots'       => !empty($userLots) ? $userLots : 'Non spécifié',
                    ]],
                ];

                $pdf = \PDF::setOptions([
                    'isRemoteEnabled'      => true,
                    'isHtml5ParserEnabled' => true,
                    'defaultFont'          => 'sans-serif',
                    'dpi'                  => 150,
                ])->loadView('pdf.appel_fonds', $pdfData);

                $nomFichier   = "AF_{$appel->af_identifier}_{$userDb->id}.pdf";
                $cheminAbsolu = "{$dossierAbsolu}/{$nomFichier}";
                file_put_contents($cheminAbsolu, $pdf->output());
                $fichiersPdf[] = $cheminAbsolu;
                $documentsCrees++;
            }

            $nomZip    = "Appel_Fonds_{$appel->af_identifier}.zip";
            $cheminZip = storage_path("app/temp/{$nomZip}");
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0775, true);
            }

            $zip = new \ZipArchive();
            if ($zip->open($cheminZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new Exception("Impossible de créer le fichier ZIP.");
            }
            foreach ($fichiersPdf as $fichier) {
                $zip->addFile($fichier, basename($fichier));
            }
            $zip->close();

            DB::table('appels_fonds')->where('af_identifier', $appel->af_identifier)->update([
                'is_generated'     => true,
                'number_generated' => $documentsCrees,
                'updated_at'       => now(),
            ]);

            DB::commit();

            return response()->download($cheminZip, $nomZip, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Erreur generer: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function envoyer(Request $request)
    {
        $request->validate(['af_identifier' => 'required|string']);

        $sp_id = (string) $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        DB::beginTransaction();
        try {
            $appel = DB::table('appels_fonds')
                ->where('af_identifier', (string) $request->af_identifier)
                ->first();

            if (!$appel || !$appel->is_generated) {
                return response()->json(['success' => false, 'message' => "Veuillez d'abord générer l'appel de fonds avant de l'envoyer."], 400);
            }

            if ($appel->is_sent) {
                return response()->json(['success' => false, 'message' => "Cet appel a déjà été envoyé."], 400);
            }

            $balanceCol = ($appel->type_charge === 'previsionnel') ? 'balance_prev' : 'balance_trav';
            if (!Schema::hasColumn('user_as_owner', $balanceCol)) {
                $balanceCol = Schema::hasColumn('user_as_owner', 'balance_prev') ? 'balance_prev' : 'solde';
            }

            $proprietaires = DB::table('appf_to_owner')
                ->where('af_identifier', (string) $appel->af_identifier)
                ->get();

            $numberSent = 0;

            foreach ($proprietaires as $p) {
                $row = DB::table('user_as_owner')
                    ->whereRaw('CAST(user_id AS VARCHAR) = ?', [(string) $p->user_id])
                    ->whereRaw('CAST(propriete_id AS VARCHAR) = ?', [$sp_id])
                    ->first();

                if (!$row) continue;

                $soldeActuel  = (float) ($row->$balanceCol ?? 0);
                $nouveauSolde = $soldeActuel - (float) $p->montant_du;

                DB::table('user_as_owner')
                    ->whereRaw('CAST(user_id AS VARCHAR) = ?', [(string) $p->user_id])
                    ->whereRaw('CAST(propriete_id AS VARCHAR) = ?', [$sp_id])
                    ->update([$balanceCol => $nouveauSolde]);

                $userDb = DB::table('users')->where('id', $p->user_id)->first();
                
                if ($userDb && !empty($userDb->email)) {
                    $pdfPath = storage_path("app/public/proprietes/{$sp_id}/appels_fonds/{$appel->af_identifier}/AF_{$appel->af_identifier}_{$p->user_id}.pdf");
                    
                    if (file_exists($pdfPath)) {
                        try {
                            $nomProprio = $userDb->full_name ?? 'Copropriétaire';
                            $dateLimite = \Carbon\Carbon::parse($appel->due_date)->format('d/m/Y');
                            
                            $messageText = "Bonjour {$nomProprio},\n\n"
                                         . "Veuillez trouver ci-joint votre appel de fonds (" . ($appel->title ?? 'Facture') . ").\n"
                                         . "Montant à régler : " . number_format($p->montant_du, 2, ',', ' ') . " MAD.\n"
                                         . "Date limite de paiement : {$dateLimite}.\n\n"
                                         . "Cordialement,\nLe Syndic.";

                            Mail::raw($messageText, function ($message) use ($userDb, $pdfPath, $appel) {
                                $message->to($userDb->email)
                                        ->subject("Votre Appel de Fonds - " . ($appel->title ?? 'Syndify'))
                                        ->attach($pdfPath); 
                            });
                            
                            Log::info("📧 Appel de fonds envoyé à : {$userDb->email}");
                        } catch (\Exception $e) {
                            Log::error("❌ Erreur email AF pour {$userDb->email} : " . $e->getMessage());
                        }
                    }
                }

                $numberSent++;
            }

            DB::table('appels_fonds')
                ->where('af_identifier', (string) $appel->af_identifier)
                ->update([
                    'is_sent'     => true,
                    'number_sent' => $numberSent,
                    'updated_at'  => now(),
                ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => "Appel envoyé par e-mail à {$numberSent} copropriétaire(s)."]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

    public function details(Request $request)
    {
        $request->validate(['af_identifier' => 'required|string']);

        $sp_id = (string) $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $appel = DB::table('appels_fonds')->where('af_identifier', (string) $request->af_identifier)->first();
        if (!$appel) return response()->json(['success' => false, 'message' => "Introuvable."], 404);

        $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        $expectedBalance = ($appel->type_charge === 'previsionnel') ? 'balance_prev' : 'balance_trav';
        $balanceCol = Schema::hasColumn('user_as_owner', $expectedBalance)
                    ? $expectedBalance
                    : (Schema::hasColumn('user_as_owner', 'balance_prev') ? 'balance_prev' : 'solde');

        $details = DB::table('appf_to_owner')
            ->join('users', function($join) {
                $join->whereRaw('users.id::text = appf_to_owner.user_id::text');
            })
            ->join('user_as_owner', function($join) {
                $join->whereRaw('user_as_owner.user_id::text = appf_to_owner.user_id::text');
            })
            ->where('user_as_owner.' . $propOwnerCol, '=', $sp_id)
            ->where('appf_to_owner.af_identifier', '=', (string) $appel->af_identifier)
            ->where('appf_to_owner.montant_du', '>', 0)
            ->select(
                'appf_to_owner.*',
                'users.full_name as owner_name',
                'users.email as email',
                'user_as_owner.' . $balanceCol . ' as solde_actuel'
            )
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'appel'  => $appel,
                'lignes' => $details
            ]
        ]);
    }

    // ==========================================
    // 8. SUPPRIMER UN APPEL DE FONDS
    // ==========================================
    public function supprimer(Request $request)
    {
        $request->validate(['af_identifier' => 'required|string']);

        $sp_id = $this->getProprieteId($request); // 🟢 FIX
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $appel = DB::table('appels_fonds')->where('af_identifier', (string) $request->af_identifier)->first();
        if (!$appel) return response()->json(['success' => false, 'message' => "Introuvable."], 404);

        if ($appel->is_sent) {
            return response()->json(['success' => false, 'message' => "Impossible de supprimer un appel déjà envoyé."], 400);
        }

        DB::beginTransaction();
        try {
            DB::table('appels_fonds')->where('af_identifier', (string) $request->af_identifier)->delete(); 
            DB::commit();
            return response()->json(['success' => true, 'message' => "Appel supprimé."]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }
}