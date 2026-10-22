<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;

class ImpayeController extends Controller
{
    // 🟢 Helpers
    private function getPropCol() { return Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id'; }
    private function getOwnerCol() { return Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier'; }
    private function getReminderCol() { return Schema::hasColumn('reminders', 'propriete_id') ? 'propriete_id' : 'sp_identifier'; }

    // ========================================================
    // 🟢 FONCTION SÉCURISÉE AVEC AUTHENTIFICATION RÉELLE
    // ========================================================
    private function getProprieteId(Request $request)
    {
        $userId = auth()->id(); 
        if (!$userId) return null; 

        $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        // 1. كنقراو residence_id من (Headers -> Body -> Cookie)
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

    // ==========================================
    // 1. LES IMPAYÉS
    // ==========================================
    public function listeImpayes(Request $request)
    {
        // 🟢 FIX: دوزنا الـ $request للدالة
        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $propCol  = $this->getPropCol();
        $ownerCol = $this->getOwnerCol();

        try {
            $residence = DB::table('proprietes')->where($propCol, $sp_id)->first();

            $hasTrav  = Schema::hasColumn('user_as_owner', 'balance_trav');
            $hasSolde = Schema::hasColumn('user_as_owner', 'solde');

            $query = DB::table('user_as_owner')
                ->join('users', 'user_as_owner.user_id', '=', 'users.id')
                ->where("user_as_owner.$ownerCol", $sp_id)
                ->where(function ($q) use ($hasTrav, $hasSolde) {
                    $q->where('user_as_owner.balance_prev', '<', 0);
                    if ($hasTrav)  $q->orWhere('user_as_owner.balance_trav', '<', 0);
                    if ($hasSolde) $q->orWhere('user_as_owner.solde', '<', 0);
                });

            $selects = [
                'users.id as user_id',
                'users.identifier as su_identifier',
                'users.full_name',
                'users.email',
                'users.tel',
                'user_as_owner.balance_prev'
            ];

            if ($hasTrav)  $selects[] = 'user_as_owner.balance_trav';
            if ($hasSolde) $selects[] = 'user_as_owner.solde';

            $impayes = $query->select($selects)->get();

            $resultats = $impayes->map(function ($impaye) {
                $totalDu = abs(min($impaye->balance_prev ?? 0, 0))
                         + abs(min($impaye->balance_trav ?? 0, 0))
                         + abs(min($impaye->solde        ?? 0, 0));

                return [
                    'user_id'       => $impaye->user_id,
                    'su_identifier' => !empty($impaye->su_identifier) ? $impaye->su_identifier : (string) $impaye->user_id,
                    'full_name'     => $impaye->full_name ?? 'Sans Nom',
                    'email'         => $impaye->email,
                    'tel'           => $impaye->tel,
                    'balance_prev'  => $impaye->balance_prev ?? 0,
                    'balance_trav'  => $impaye->balance_trav ?? 0,
                    'total_du'      => $totalDu
                ];
            });

            return response()->json([
                'success'        => true,
                'residence'      => [
                    'nom'     => $residence->nom     ?? 'Résidence',
                    'adresse' => $residence->address ?? 'Adresse non définie'
                ],
                'data'           => $resultats,
                'total_impayes'  => $resultats->sum('total_du')
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 2. LES RAPPELS
    // ==========================================
    public function listeRappels(Request $request)
    {
        // 🟢 FIX: دوزنا الـ $request للدالة
        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        if (!Schema::hasTable('reminders')) {
            return response()->json(['success' => true, 'data' => [], 'pagination' => ['last_id' => 0, 'is_there_more' => false]]);
        }

        $reminderCol = $this->getReminderCol();
        $role    = strtolower($request->role    ?? 'syndic');
        $userId  = $request->user_id            ?? 1;
        $last_id = $request->last_id            ?? 0;
        $limit   = 20;

        try {
            $query = DB::table('reminders')
                ->join('users', 'reminders.user_id', '=', 'users.id')
                ->where("reminders.$reminderCol", $sp_id)
                ->where('reminders.id', '>', $last_id);

            if ($role === 'proprietaire') {
                $query->where('reminders.user_id', $userId);
            }

            $rappels = $query
                ->select('reminders.*', 'users.full_name', 'users.identifier as su_identifier')
                ->orderBy('reminders.id', 'asc')
                ->limit($limit)
                ->get();

            $new_last_id   = $rappels->max('id') ?? $last_id;
            $is_there_more = false;

            if ($rappels->count() === $limit) {
                $moreQuery = DB::table('reminders')
                    ->where($reminderCol, $sp_id)
                    ->where('id', '>', $new_last_id);
                if ($role === 'proprietaire') $moreQuery->where('user_id', $userId);
                $is_there_more = $moreQuery->exists();
            }

            return response()->json([
                'success' => true,
                'data'    => $rappels,
                'pagination' => [
                    'last_id'       => $new_last_id,
                    'is_there_more' => $is_there_more
                ]
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 3. ENVOYER UN RAPPEL
    // ==========================================
    public function envoyerRappel(Request $request)
    {
        $id_user = $request->input('su_identifier');
        $user_id = $request->input('user_id');

        Log::info("🔍 [envoyerRappel] su_identifier=[{$id_user}] user_id=[{$user_id}]");

        if (!$id_user && !$user_id) {
            return response()->json(['success' => false, 'message' => "L'identifiant du copropriétaire est manquant."], 400);
        }

        // 🟢 FIX: دوزنا الـ $request للدالة
        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $ownerCol = $this->getOwnerCol();

        DB::beginTransaction();
        try {
            $user   = null;
            $lookup = $id_user ?: $user_id;

            $user = DB::table('users')->where('id', $lookup)->first();
            
            if (!$user) {
                $user = DB::table('users')->where('identifier', $lookup)->first();
            }

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Utilisateur introuvable.'], 404);
            }

            $solde = DB::table('user_as_owner')
                ->where('user_id', $user->id)
                ->where($ownerCol, $sp_id)
                ->first();

            if (!$solde) {
                return response()->json(['success' => false, 'message' => 'Aucun compte lié à cette propriété.'], 404);
            }

            $prev    = min($solde->balance_prev ?? 0, 0);
            $trav    = min($solde->balance_trav ?? 0, 0);
            $totalDu = abs($prev) + abs($trav);

            if ($totalDu == 0) {
                return response()->json(['success' => false, 'message' => "Ce copropriétaire n'a aucun impayé."], 400);
            }

            $pdfFileName         = "rappel_" . $user->id . "_" . time() . ".pdf";
            $dossierPath         = "proprietes/{$sp_id}/reminders";
            $absoluteDossierPath = storage_path("app/public/{$dossierPath}");

            if (!file_exists($absoluteDossierPath)) {
                mkdir($absoluteDossierPath, 0775, true);
            }

            $absolutePdfPath = "{$absoluteDossierPath}/{$pdfFileName}";

            $syndic = auth()->user();
            $propCol = $this->getPropCol();
            $residence = DB::table('proprietes')->where($propCol, $sp_id)->first();

            $userLots = DB::table('user_owner_unit')
                ->join('units', 'user_owner_unit.unit_id', '=', 'units.id')
                ->where('user_owner_unit.user_id', $user->id)
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

            $reference = 'RAP-' . date('Ym') . '-' . $user->id;

            $generator = new BarcodeGeneratorPNG();
            $barcodeBase64 = base64_encode($generator->getBarcode(md5($reference), $generator::TYPE_CODE_128));

            $pdfData = [
                'reference'   => $reference,
                'date_rappel' => now()->format('Y-m-d'),
                'adresse'     => $residence->address ?? 'Adresse non définie',
                'telephone'   => $syndic->tel ?? '06 00 00 00 00',
                'email'       => $syndic->email ?? 'contact@syndic.com',
                'nom'         => $user->full_name ?? $user->email ?? 'Copropriétaire',
                'montant_du'  => $totalDu,
                'lots'        => !empty($userLots) ? $userLots : 'Non spécifié',
                'barcode'     => $barcodeBase64
            ];

            $pdf = \PDF::setOptions([
                'isRemoteEnabled'      => true,
                'isHtml5ParserEnabled' => true,
                'defaultFont'          => 'sans-serif',
                'dpi'                  => 150,
            ])->loadView('pdf.rappel', $pdfData); 

            $pdf->save($absolutePdfPath);

            try {
                $reminderCol = $this->getReminderCol();
                DB::table('reminders')->insert([
                    $reminderCol   => $sp_id,
                    'user_id'      => $user->id,
                    'document_url' => '/storage/' . $dossierPath . '/' . $pdfFileName,
                    'amount_due'   => $totalDu,
                    'status'       => 'envoyé',
                    'created_at'   => now(),
                    'updated_at'   => now()
                ]);
            } catch (\Exception $e) {
                Log::warning("Table reminders introuvable, insertion ignorée : " . $e->getMessage());
            }

            if (!empty($user->email)) {
                try {
                    $nomProprio  = $user->full_name ?? 'Copropriétaire';
                    $messageText = "Bonjour {$nomProprio},\n\n"
                                 . "Veuillez trouver ci-joint votre lettre de rappel de paiement.\n"
                                 . "Le montant total de vos impayés s'élève à : " . number_format($totalDu, 2, ',', ' ') . " MAD.\n\n"
                                 . "Merci de régulariser votre situation dans les plus brefs délais pour éviter des frais de retard.\n\n"
                                 . "Cordialement,\nLe Syndic.";

                    Mail::raw($messageText, function ($message) use ($user, $absolutePdfPath) {
                        $message->to($user->email)
                                ->subject("Rappel de paiement - Syndify")
                                ->attach($absolutePdfPath);
                    });
                } catch (\Exception $e) {}
            }

            try {
                $notifCol = Schema::hasColumn('notifications', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
                DB::table('notifications')->insert([
                    'user_id'    => $user->id,
                    $notifCol    => $sp_id,
                    'title'      => 'Rappel de paiement',
                    'message'    => "Vous avez un impayé de {$totalDu} DH. Veuillez consulter vos documents.",
                    'is_read'    => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } catch (\Exception $e) {}

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => "Rappel généré et envoyé par email avec succès."
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("❌ [envoyerRappel] Erreur : " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }
}