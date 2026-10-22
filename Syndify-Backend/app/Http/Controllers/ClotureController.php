<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ClotureController extends Controller
{
private function getProprieteId(Request $request)
    {
        $userId = auth()->id(); 
        if (!$userId) return null; 

        $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        // 🟢 Nqraw mn 'propriete_id' AWLA 'residence_id' (mn Body, Header, awla Cookie)
        $residenceId = $request->input('propriete_id') 
                    ?? $request->input('residence_id') 
                    ?? $request->header('residence_id') 
                    ?? $request->header('propriete_id') 
                    ?? $request->cookie('residence_id');

        if ($residenceId) {
            $link = DB::table('user_as_owner')
                ->where('user_id', (string)$userId)
                ->where($propOwnerCol, $residenceId)
                ->first();
            if ($link) return $residenceId;
        }

        // Fallback
        $link = DB::table('user_as_owner')->where('user_id', $userId)->first();
        return $link ? $link->$propOwnerCol : null;
    }

    // ==========================================
    // 1. CHARGEMENT D'UNE CLÔTURE (Calculs)
    // ==========================================
    public function charger(Request $request)
    {
        $request->validate(['se_identifier' => 'required|string']);

        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $se_id = $request->input('se_identifier');

        $propIdCol = Schema::hasColumn('exercices', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        try {
            $propIdCol_propriete = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
            $residence = DB::table('proprietes')->where($propIdCol_propriete, $sp_id)->first();

            $exercice = DB::table('exercices')->where('se_identifier', $se_id)->where($propIdCol, $sp_id)->first();
            if (!$exercice) return response()->json(['success' => false, 'message' => 'Exercice introuvable.'], 404);

            $cloture = DB::table('clotures')->where('se_identifier', $se_id)->first();
            $clotureStatus = $cloture ? $cloture->status : 0;

            $cp = DB::table('charges_previsionnelles')->where('se_identifier', $se_id)->first();
            $ct = DB::table('charges_travaux')->where('se_identifier', $se_id)->first();

            $prev_budget       = $cp ? $cp->budget : 0;
            $prev_encaissement = $cp ? $cp->total_encaissements : 0;
            $prev_depense      = $cp ? $cp->total_depenses : 0;
            $prev_reste        = max(0, $prev_encaissement - $prev_depense);
            $prev_du           = max(0, $prev_budget - $prev_encaissement);

            $trav_budget       = $ct ? $ct->budget : 0;
            $trav_encaissement = $ct ? $ct->total_encaissements : 0;
            $trav_depense      = $ct ? $ct->total_depenses : 0;
            $trav_reste        = max(0, $trav_encaissement - $trav_depense);
            $trav_du           = max(0, $trav_budget - $trav_encaissement);

            $cles_prev = [];
            if ($cp) {
                $cles_prev = DB::table('bp_to_key')
                    ->join('cle_repartitions', 'bp_to_key.cle_repartition_id', '=', 'cle_repartitions.id')
                    ->where('scp_identifier', $cp->scp_identifier)
                    ->select('cle_repartitions.nom', 'bp_to_key.budget', 'bp_to_key.depenses')
                    ->get();
            }

            $appels_exceptionnels_prev = DB::table('appels_fonds')
                ->where('se_identifier', $se_id)->where('type_charge', 'previsionnel')->where('sub_type', 'exceptionnel')->get();

            $exceptionnel_prev_budget = $appels_exceptionnels_prev->sum('amount');
            $exceptionnel_prev_enc    = DB::table('encaissements')->where('se_identifier', $se_id)->where('sub_type_charges', 'exceptionnel')->where('type_charges', 'previsionnel')->sum('amount');
            $exceptionnel_prev_dep    = DB::table('depenses')->where('se_identifier', $se_id)->where('sub_type_charges', 'exceptionnel')->where('type_charges', 'previsionnel')->sum('amount');

            $grand_budget       = $prev_budget + $trav_budget + $exceptionnel_prev_budget;
            $grand_encaissement = $prev_encaissement + $trav_encaissement + $exceptionnel_prev_enc;
            $grand_depense      = $prev_depense + $trav_depense + $exceptionnel_prev_dep;
            $grand_reste        = max(0, $grand_encaissement - $grand_depense);
            $grand_du           = max(0, $grand_budget - $grand_encaissement);

            return response()->json([
                'success' => true,
                'residence' => [
                    'nom'    => $residence->nom ?? 'Résidence',
                    'adresse'=> $residence->address ?? 'Adresse non définie'
                ],
                'data' => [
                    'exercice_status'  => $exercice->status,
                    'cloture_status'   => $clotureStatus,
                    'previsionnel' => [
                        'resume' => [
                            'budget'        => $prev_budget,
                            'encaissements' => $prev_encaissement,
                            'depenses'      => $prev_depense,
                            'reste'         => $prev_reste,
                            'du'            => $prev_du
                        ],
                        'cles' => $cles_prev,
                        'exceptionnel' => [
                            'budget'        => $exceptionnel_prev_budget,
                            'encaissements' => $exceptionnel_prev_enc,
                            'depenses'      => $exceptionnel_prev_dep,
                            'reste'         => max(0, $exceptionnel_prev_enc - $exceptionnel_prev_dep),
                            'du'            => max(0, $exceptionnel_prev_budget - $exceptionnel_prev_enc),
                            'appels'        => $appels_exceptionnels_prev
                        ]
                    ],
                    'travaux' => [
                        'resume' => [
                            'budget'        => $trav_budget,
                            'encaissements' => $trav_encaissement,
                            'depenses'      => $trav_depense,
                            'reste'         => $trav_reste,
                            'du'            => $trav_du
                        ]
                    ],
                    'grand_total' => [
                        'budget'        => $grand_budget,
                        'encaissements' => $grand_encaissement,
                        'depenses'      => $grand_depense,
                        'reste'         => $grand_reste,
                        'du'            => $grand_du
                    ],
                    'cloture_saved_data' => $cloture
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 2. ENREGISTRER LA CLÔTURE (Brouillon)
    // ==========================================
    public function enregistrer(Request $request)
    {
        $request->validate([
            'se_identifier'      => 'required|string',
            'reste_choice_prev'  => 'nullable|integer',
            'du_choice_prev'     => 'nullable|integer',
            'send_reminders_prev'=> 'boolean',
            'reste_choice_trav'  => 'nullable|integer',
            'du_choice_trav'     => 'nullable|integer',
            'send_reminders_trav'=> 'boolean',
        ]);

        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $propIdCol = Schema::hasColumn('exercices', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        DB::beginTransaction();
        try {
            $exercice = DB::table('exercices')->where('se_identifier', $request->se_identifier)->where($propIdCol, $sp_id)->first();
            if (!$exercice || !in_array($exercice->status, ['en cours', 'A clore', 'en attente'])) {
                return response()->json(['success' => false, 'message' => "L'exercice n'est pas ouvert."], 400);
            }

            $clotureData = [
                'se_identifier'      => $request->se_identifier,
                'status'             => 0,
                'reste_choice_prev'  => $request->reste_choice_prev,
                'du_choice_prev'     => $request->du_choice_prev,
                'send_reminders_prev'=> $request->send_reminders_prev ?? false,
                'reste_choice_trav'  => $request->reste_choice_trav,
                'du_choice_trav'     => $request->du_choice_trav,
                'send_reminders_trav'=> $request->send_reminders_trav ?? false,
                'updated_at'         => now()
            ];

            $exists = DB::table('clotures')->where('se_identifier', $request->se_identifier)->exists();
            if ($exists) {
                DB::table('clotures')->where('se_identifier', $request->se_identifier)->update($clotureData);
            } else {
                $clotureData['created_at'] = now();
                DB::table('clotures')->insert($clotureData);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Clôture sauvegardée en brouillon.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 3. FINALISER LA CLÔTURE (Clôture Définitive)
    // ==========================================
    public function finaliser(Request $request)
    {
        $request->validate(['se_identifier' => 'required|string']);

        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        DB::beginTransaction();
        try {
            $cloture = DB::table('clotures')->where('se_identifier', $request->se_identifier)->first();
            if (!$cloture) return response()->json(['success' => false, 'message' => 'Veuillez enregistrer la clôture avant de finaliser.'], 400);
            if ($cloture->status == 1) return response()->json(['success' => false, 'message' => 'Cette clôture est déjà finalisée.'], 400);

            // 1. Jbed les données pour le PDF
            $propIdCol_propriete = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
            $residence = DB::table('proprietes')->where($propIdCol_propriete, $sp_id)->first();
            $exercice  = DB::table('exercices')->where('se_identifier', $request->se_identifier)->first();
            $cp        = DB::table('charges_previsionnelles')->where('se_identifier', $request->se_identifier)->first();
            $ct        = DB::table('charges_travaux')->where('se_identifier', $request->se_identifier)->first();

            // Totaux recalculés depuis les vraies transactions
            $prev_enc = DB::table('encaissements')->where('se_identifier', $request->se_identifier)->where('type_charges', 'previsionnel')->sum('amount');
            $prev_dep = DB::table('depenses')->where('se_identifier', $request->se_identifier)->where('type_charges', 'previsionnel')->sum('amount');
            $trav_enc = DB::table('encaissements')->where('se_identifier', $request->se_identifier)->where('type_charges', 'travaux')->sum('amount');
            $trav_dep = DB::table('depenses')->where('se_identifier', $request->se_identifier)->where('type_charges', 'travaux')->sum('amount');

            $prev_budget = $cp ? $cp->budget : 0;
            $trav_budget = $ct ? $ct->budget : 0;

            $grand_budget       = $prev_budget + $trav_budget;
            $grand_encaissement = $prev_enc + $trav_enc;
            $grand_depense      = $prev_dep + $trav_dep;

            $telephone = $residence->telephone ?? '';
            $email     = $residence->email ?? '';

            // Logo
            $logo_path = null;
            if ($residence && !empty($residence->logo)) {
                $candidate = storage_path('app/public/' . $residence->logo);
                if (file_exists($candidate)) {
                    $logo_path = $candidate;
                }
            }

            // 2. ✅ Générer le PDF avec la blade professionnelle
            $pdf = Pdf::loadView('pdf.rapport-cloture', [
                'se_identifier'     => $request->se_identifier,
                'residence_nom'     => $residence->nom ?? 'Résidence',
                'residence_adresse' => $residence->address ?? 'Adresse non définie',
                'exercice_start'    => $exercice->start_date ?? now(),
                'exercice_end'      => $exercice->end_date ?? now(),
                'telephone'         => $telephone,
                'email'             => $email,
                'logo_path'         => $logo_path,
                'prev' => [
                    'budget'        => $prev_budget,
                    'encaissements' => $prev_enc,
                    'depenses'      => $prev_dep,
                    'reste'         => max(0, $prev_enc - $prev_dep),
                    'du'            => max(0, $prev_budget - $prev_enc),
                ],
                'trav' => [
                    'budget'        => $trav_budget,
                    'encaissements' => $trav_enc,
                    'depenses'      => $trav_dep,
                    'reste'         => max(0, $trav_enc - $trav_dep),
                    'du'            => max(0, $trav_budget - $trav_enc),
                ],
                'grand' => [
                    'budget'        => $grand_budget,
                    'encaissements' => $grand_encaissement,
                    'depenses'      => $grand_depense,
                    'reste'         => max(0, $grand_encaissement - $grand_depense),
                    'du'            => max(0, $grand_budget - $grand_encaissement),
                ],
            ])->setOption(['isRemoteEnabled' => true]);

            // 3. Sauvegarde du PDF
            $pdfPath    = "proprietes/{$sp_id}/clotures/{$request->se_identifier}_rapport_cloture.pdf";
            Storage::disk('public')->put($pdfPath, $pdf->output());
            $documentUrl = "storage/" . $pdfPath;

            // 4. Mettre à jour la clôture
            DB::table('clotures')->where('se_identifier', $request->se_identifier)->update([
                'status'      => 1,
                'report_link' => $documentUrl,
                'updated_at'  => now()
            ]);

            // 5. Mettre à jour l'exercice
            DB::table('exercices')->where('se_identifier', $request->se_identifier)->update([
                'status'     => 'Clos',
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Clôture finalisée avec succès.', 'report_link' => $documentUrl]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}