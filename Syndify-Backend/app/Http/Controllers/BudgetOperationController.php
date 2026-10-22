<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon; 
use Exception;

class BudgetOperationController extends Controller
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
            // كنتأكدو بلي هاد اليوزر عندو الحق فهاد الإقامة
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

    // ==========================================
    // 1. CHARGEMENT DES DONNÉES
    // ==========================================
    public function chargerDonnees(Request $request)
    {
      
        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $request->validate([
            'exercise' => 'nullable|string',
            'type'     => 'required|in:previsionnel,travaux'
        ]);

        $type = $request->type; // ✅ 'previsionnel' ou 'travaux'

        $propIdCol = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
        $residence = DB::table('proprietes')->where($propIdCol, $sp_id)->first();

// ✅ FIX — assign $se_id dima qbel
$se_id = $request->exercise ?? null;

if (!$se_id) {
    $latestEx = DB::table('exercices')
        ->where('propriete_id', $sp_id)
        ->where('status', 'en cours')
        ->first();

    if (!$latestEx) {
        $latestEx = DB::table('exercices')
            ->where('propriete_id', $sp_id)
            ->orderBy('start_date', 'desc')
            ->first();
    }

    if (!$latestEx) return response()->json([
        'success' => true,
        'residence' => [
            'nom'     => $residence->nom ?? 'Résidence',
            'adresse' => $residence->address ?? 'Adresse non définie'
        ],
        'data' => [
            'operations' => [],
            'totaux'     => null,
            'pagination' => ['last_enc_id' => 0, 'last_dep_id' => 0, 'is_there_more' => false]
        ]
    ]);

    $se_id = $latestEx->se_identifier; // ✅ Set hna
}

// ✅ Maintenant $se_id defined dima
$tableCharges = $type === 'previsionnel' ? 'charges_previsionnelles' : 'charges_travaux';
$totaux = DB::table($tableCharges)->where('se_identifier', $se_id)->first();

        if ($totaux) {
            // ✅ FIX: Sum filtré par type_charges
            $totaux->total_encaissements = DB::table('encaissements')
                ->where('se_identifier', $se_id)
                ->where('type_charges', $type)
                ->sum('amount') ?? 0;

            $totaux->total_depenses = DB::table('depenses')
                ->where('se_identifier', $se_id)
                ->where('type_charges', $type)
                ->sum('amount') ?? 0;
        }

        $last_enc_id = $request->last_enc_id ?? 0;
        $last_dep_id = $request->last_dep_id ?? 0;
        $limit = 20;

        // ✅ FIX: Encaissements filtrés par type_charges
        $encaissements = DB::table('encaissements')
            ->where('se_identifier', $se_id)
            ->where('type_charges', $type)
            ->where('id', '>', $last_enc_id)
            ->select(
                'id as origin_id',
                'date',
                'title as libelle',
                'amount as montant',
                DB::raw("'Encaissement' as type"),
                'sub_type_charges'
            )
            ->limit($limit)
            ->get();

        // ✅ FIX: Dépenses filtrées par type_charges
        $depenses = DB::table('depenses')
            ->where('se_identifier', $se_id)
            ->where('type_charges', $type)
            ->where('id', '>', $last_dep_id)
            ->select(
                'id as origin_id',
                'date',
                'title as libelle',
                DB::raw("amount * -1 as montant"),
                DB::raw("'Dépense' as type"),
                'sub_type_charges'
            )
            ->limit($limit)
            ->get();

        $operations = $encaissements->merge($depenses)->sortByDesc('date')->take($limit)->values();

        $new_last_enc_id = $operations->where('type', 'Encaissement')->max('origin_id') ?? $last_enc_id;
        $new_last_dep_id = $operations->where('type', 'Dépense')->max('origin_id') ?? $last_dep_id;

        $is_there_more = DB::table('encaissements')
                ->where('se_identifier', $se_id)
                ->where('type_charges', $type)
                ->where('id', '>', $new_last_enc_id)
                ->exists()
            || DB::table('depenses')
                ->where('se_identifier', $se_id)
                ->where('type_charges', $type)
                ->where('id', '>', $new_last_dep_id)
                ->exists();

        return response()->json([
            'success'   => true,
            'residence' => [
                'nom'     => $residence->nom ?? 'Résidence',
                'adresse' => $residence->address ?? 'Adresse non définie'
            ],
            'data' => [
                'se_identifier' => $se_id,
                'totaux'        => $totaux,
                'operations'    => $operations,
                'pagination'    => [
                    'last_enc_id'  => $new_last_enc_id,
                    'last_dep_id'  => $new_last_dep_id,
                    'is_there_more' => $is_there_more
                ]
            ]
        ]);
    }

// ==========================================
// 2. AJOUTER UN ENCAISSEMENT
// ==========================================
    public function ajouterEncaissement(Request $request)
    {
        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $request->validate([
            'se_identifier'    => 'required',
            'title'            => 'required',
            'owner_id'         => 'required',
            'type_charges'     => 'required|in:previsionnel,travaux',
            'sub_type_charges' => 'required|in:planifié,exceptionnel',
            'amount'           => 'required|numeric|min:0',
            'date'             => 'required|date'
        ]);

        $check = $this->checkExercice($request->se_identifier);
        if ($check !== true) return response()->json(['success' => false, 'message' => $check], 403);

        DB::beginTransaction();
        try {
            $sen_id = 'ENC-' . time() . rand(10, 99);

            $tableCharges = $request->type_charges === 'previsionnel' ? 'charges_previsionnelles' : 'charges_travaux';
            DB::table($tableCharges)
                ->where('se_identifier', $request->se_identifier)
                ->increment('total_encaissements', $request->amount);

            $balanceCol = 'balance_prev';

            DB::table('user_as_owner')
                ->where('user_id', $request->owner_id)
                ->where('propriete_id', $sp_id)
                ->increment($balanceCol, $request->amount);

            // 🟢 1. إعداد الـ PDF (جلب المعلومات)
            $userDb = DB::table('users')->where('id', $request->owner_id)->first();
            $syndic = auth()->user();
            $propIdCol = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
            $residence = DB::table('proprietes')->where($propIdCol, $sp_id)->first();

            // جلب معلومات العقارات (Lots) 
            $userLots = DB::table('user_owner_unit')
                ->join('units', 'user_owner_unit.unit_id', '=', 'units.id')
                ->where('user_owner_unit.user_id', $request->owner_id)
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

            $exercice = DB::table('exercices')->where('se_identifier', $request->se_identifier)->first();
            $periodeText = $exercice ? "Du " . Carbon::parse($exercice->start_date)->format('d/m/Y') . " au " . Carbon::parse($exercice->end_date)->format('d/m/Y') : 'l\'exercice en cours';

            // إعداد البيانات لملف الـ Blade
            $pdfData = [
                'reference'       => $sen_id,
                'date_paiement'   => $request->date,
                'adresse'         => $residence->address ?? 'Adresse non définie',
                'telephone'       => $syndic->tel ?? '06 00 00 00 00',
                'email'           => $syndic->email ?? 'contact@syndic.com',
                'iban'            => $residence->iban ?? '[IBAN]',
                'nom'             => $userDb->full_name ?? $userDb->email ?? 'Copropriétaire',
                'montant'         => $request->amount,
                'lots'            => !empty($userLots) ? $userLots : 'Non spécifié',
                'mode_paiement'   => 'Virement bancaire / Espèces', 
                'nom_copropriete' => $residence->nom ?? 'la copropriété',
                'periode'         => $periodeText,
                'type_frais'      => ucfirst($request->type_charges) . " (" . $request->sub_type_charges . ")"
            ];

            // 🟢 2. توليد وحفظ الـ PDF
            $pdf = \PDF::setOptions([
                'isRemoteEnabled'      => true,
                'isHtml5ParserEnabled' => true,
                'defaultFont'          => 'sans-serif',
                'dpi'                  => 150,
            ])->loadView('pdf.recu_encaissement', $pdfData);

            $dossierAbsolu = storage_path("app/public/proprietes/{$sp_id}/encaissements");
            if (!file_exists($dossierAbsolu)) {
                mkdir($dossierAbsolu, 0775, true);
            }
            $pdfFileName = "{$sen_id}.pdf";
            $cheminAbsolu = "{$dossierAbsolu}/{$pdfFileName}";
            
            // حفظ الـ PDF
            file_put_contents($cheminAbsolu, $pdf->output());

            $path = "proprietes/{$sp_id}/encaissements/{$pdfFileName}"; // المسار اللي غيتسجل فـ DB

            // 🟢 3. تسجيل Encaissement فـ قاعدة البيانات
            DB::table('encaissements')->insert([
                'sen_identifier'   => $sen_id,
                'se_identifier'    => $request->se_identifier,
                'owner_id'         => $request->owner_id,
                'title'            => $request->title,
                'amount'           => $request->amount,
                'date'             => $request->date,
                'type_charges'     => $request->type_charges,
                'sub_type_charges' => $request->sub_type_charges,
                'document_url'     => '/storage/' . $path, // تسجيل الرابط
                'created_at'       => now(),
                'updated_at'       => now()
            ]);

            DB::commit();

            // 🟢 4. إرسال الإيميل مع الـ PDF بعد نجاح العملية 
            if (!empty($userDb->email)) {
                try {
                    $nomProprio = $userDb->full_name ?? 'Copropriétaire';
                    $messageText = "Bonjour {$nomProprio},\n\n"
                                 . "Nous vous confirmons la réception de votre paiement d'un montant de " . number_format($request->amount, 2, ',', ' ') . " MAD.\n"
                                 . "Veuillez trouver ci-joint votre reçu d'encaissement officiel.\n\n"
                                 . "Cordialement,\nLe Syndic.";

                    Mail::raw($messageText, function ($message) use ($userDb, $cheminAbsolu, $sen_id) {
                        $message->to($userDb->email)
                                ->subject("Votre reçu d'encaissement - " . $sen_id)
                                ->attach($cheminAbsolu);
                    });
                } catch (\Exception $e) {
                    Log::error("❌ Erreur email Encaissement pour {$userDb->email} : " . $e->getMessage());
                }
            }

            return response()->json(['success' => true, 'message' => 'Encaissement ajouté et reçu envoyé par email.']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
  // ==========================================
    // 3. AJOUTER UNE DÉPENSE
    // ==========================================
    public function ajouterDepense(Request $request)
    {
        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        // 🟢 1. زدنا Validation باش يفرض الكلي ديال القسمة إيلا ماكانش
        $request->validate([
            'se_identifier'    => 'required',
            'cle_repartition_id' => 'required', // 🔴 هادي اللي كانت nullable! دابا ولات مفروضة
            'title'            => 'required',
            'type_charges'     => 'required|in:previsionnel,travaux',
            'sub_type_charges' => 'required',
            'amount'           => 'required|numeric|min:0',
            'date'             => 'required|date'
        ], [
            // 🟢 2. ميساج مخصص اللي غيطلع فـ Angular
            'cle_repartition_id.required' => 'Veuillez sélectionner une clé de répartition pour cette dépense.' 
        ]);

        $check = $this->checkExercice($request->se_identifier);
        if ($check !== true) return response()->json(['success' => false, 'message' => $check], 403);

        DB::beginTransaction();
        try {
            $sdep_id = 'DEP-' . time() . rand(10, 99);

            $depenseId = DB::table('depenses')->insertGetId([
                'sdep_identifier'    => $sdep_id,
                'se_identifier'      => $request->se_identifier,
                'cle_repartition_id' => $request->cle_repartition_id,
                'title'              => $request->title,
                'amount'             => $request->amount,
                'date'               => $request->date,
                'type_charges'       => $request->type_charges,
                'sub_type_charges'   => $request->sub_type_charges,
                'created_at'         => now(),
                'updated_at'         => now()
            ]);

            // 🟢 3. القسمة ديال المصروف (تأكدنا بلي الكلي كاين)
            $cle = DB::table('cle_repartitions')->where('id', $request->cle_repartition_id)->first();
            
            if (!$cle) {
                 return response()->json(['success' => false, 'message' => "La clé de répartition sélectionnée est introuvable."], 400);
            }

            $total_tantiemes = $cle->tantiemes_total ?? 1000;

            $lots = DB::table('unit_to_key')
                ->join('user_owner_unit', 'unit_to_key.unit_id', '=', 'user_owner_unit.unit_id')
                ->where('unit_to_key.cle_repartition_id', $request->cle_repartition_id)
                ->where('user_owner_unit.status', 1)
                ->select('user_owner_unit.user_id as final_user_id', 'unit_to_key.tantieme')
                ->get();

            $ownerAmounts = [];
            foreach ($lots as $lot) {
                $part = $request->amount * (($lot->tantieme ?? 0) / $total_tantiemes);
                $u_id = $lot->final_user_id;
                $ownerAmounts[$u_id] = ($ownerAmounts[$u_id] ?? 0) + $part;
            }

            $balanceCol = 'balance_prev';

            foreach ($ownerAmounts as $su_id => $montant_calcule) {
                DB::table('depense_for_owner')->insert([
                    'depense_id' => $depenseId,
                    'user_id'    => $su_id,
                    'amount_due' => $montant_calcule,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                DB::table('user_as_owner')
                    ->where('user_id', $su_id)
                    ->where('propriete_id', $sp_id)
                    ->decrement($balanceCol, $montant_calcule);
            }

            // الميزانية العامة كتنقص ديما
            $tableCharges = $request->type_charges === 'previsionnel' ? 'charges_previsionnelles' : 'charges_travaux';
            DB::table($tableCharges)
                ->where('se_identifier', $request->se_identifier)
                ->increment('total_depenses', $request->amount);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Dépense ajoutée.']);
            
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => "Erreur Serveur: " . $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 4. VÉRIFIER L'EXERCICE
    // ==========================================
    private function checkExercice($se_identifier)
    {
        $ex = DB::table('exercices')->where('se_identifier', $se_identifier)->first();

        if (!$ex) {
            return "L'exercice '{$se_identifier}' est introuvable.";
        }

        if (strtolower($ex->status) !== 'en cours') {
            return "Opération refusée : L'exercice doit être 'en cours' (Statut actuel : {$ex->status}).";
        }

        return true;
    }

    // ==========================================
    // 5. TÉLÉCHARGER LE RELEVÉ (EN PDF)
    // ==========================================
    public function telechargerReleve(Request $request)
    {
        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $request->validate([
            'exercise' => 'required|string',
            'type'     => 'required|in:previsionnel,travaux'
        ]);

        $se_id = $request->exercise;
        $type  = $request->type; // ✅ 'previsionnel' ou 'travaux'

        $propIdCol = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
        $residence = DB::table('proprietes')->where($propIdCol, $sp_id)->first();

        // ✅ FIX: Encaissements filtrés par type_charges
        $encaissements = DB::table('encaissements')
            ->where('se_identifier', $se_id)
            ->where('type_charges', $type)
            ->select(
                'date',
                'title as libelle',
                'amount as montant',
                DB::raw("'Encaissement' as type")
            )
            ->get();

        // ✅ FIX: Dépenses filtrées par type_charges
        $depenses = DB::table('depenses')
            ->where('se_identifier', $se_id)
            ->where('type_charges', $type)
            ->select(
                'date',
                'title as libelle',
                DB::raw("amount * -1 as montant"),
                DB::raw("'Dépense' as type")
            )
            ->get();

        $operations = $encaissements->merge($depenses)->sortByDesc('date')->values();

        $pdf = Pdf::loadView('pdf.releve', [
            'operations' => $operations,
            'type'       => $type,
            'exercice'   => $se_id,
            'residence'  => $residence
        ])->setOption(['isRemoteEnabled' => true]);

        return $pdf->download("Releve_{$type}_{$se_id}.pdf");
    }
}