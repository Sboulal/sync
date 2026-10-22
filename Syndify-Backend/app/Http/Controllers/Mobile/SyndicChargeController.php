<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class SyndicChargeController extends Controller
{
    private function col($table, $col, $fallback) {
        return Schema::hasColumn($table, $col) ? $col : $fallback;
    }

    // 🟢 1. REPARER L'ACCÈS À LA PROPRIÉTÉ AVEC LE CASTING POSTGRESQL (::text)
    private function getProprieteId(Request $request)
    {
        $user = $request->attributes->get('gateway_user') ?? $request->user();
        $payload = $request->attributes->get('gateway_payload');

        if (!$user) return null; 

        $userId = (string) $user->id;
        $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        $residenceId = $payload['data']['residence_id'] ?? $request->header('residence_id') ?? $request->input('residence_id');

        if ($residenceId) {
            $link = DB::table('user_as_owner')
                ->whereRaw('user_id::text = ?', [$userId]) // 🟢 FIX TYPE POSTGRES
                ->whereRaw("{$propOwnerCol}::text = ?", [(string)$residenceId]) // 🟢 FIX TYPE POSTGRES
                ->first();
            if ($link) return $residenceId;
        }

        $link = DB::table('user_as_owner')
            ->whereRaw('user_id::text = ?', [$userId]) // 🟢 FIX TYPE POSTGRES
            ->first();
        
        return $link ? $link->$propOwnerCol : null;
    }

    public function index(Request $request)
    {
        try {
            $propId = $this->getProprieteId($request);
            if (!$propId) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

            $exCol = $this->col('exercices', 'propriete_id', 'sp_identifier');
            
            $exercice = DB::table('exercices')
                ->whereRaw("{$exCol}::text = ?", [(string)$propId])
                ->where('status', 'en cours')
                ->first();

            if (!$exercice) {
                $exercice = DB::table('exercices')
                    ->whereRaw("{$exCol}::text = ?", [(string)$propId])
                    ->orderBy('start_date', 'desc')
                    ->first();
            }

            if (!$exercice) {
                // 🟢 RJ3NAHA 'appels' hit application dyal Syndic katsnaha b had smya
                return response()->json(['success' => true, 'latest_appel' => null, 'appels' => []]);
            }

            $appels = DB::table('appels_fonds')
                ->whereRaw('se_identifier::text = ?', [(string)$exercice->se_identifier])
                ->orderBy('created_at', 'desc')
                ->get();

            if ($appels->isEmpty()) {
                // 🟢 RJ3NAHA 'appels'
                return response()->json(['success' => true, 'latest_appel' => null, 'appels' => []]);
            }

            $latest = $appels->first();
            $detailsLatest = DB::table('appf_to_owner')->where('af_identifier', $latest->af_identifier)->get();
            
            $totalAppele = $latest->amount;
            $totalLots = $detailsLatest->count();
            
            $a_jour = 0; $en_retard = 0; $encaisses = 0;

            foreach ($detailsLatest as $detail) {
                $solde = (float) (DB::table('user_as_owner')->whereRaw("user_id::text = ?", [(string)$detail->user_id])->value('balance_prev') ?? 0);
                if ($solde <= 0) { 
                    $a_jour++; 
                    $encaisses += $detail->montant_du; 
                } else { 
                    $en_retard++; 
                    $encaisses += max(0, $detail->montant_du - $solde);
                }
            }

            return response()->json([
                'success' => true,
                'latest_appel' => [
                    'id' => $latest->af_identifier,
                    'title' => $latest->title,
                    'amount' => $totalAppele,
                    'lots_count' => $totalLots,
                    'a_jour' => $a_jour,
                    'en_retard' => $en_retard,
                    'encaisses' => $encaisses,
                    'restant' => max(0, $totalAppele - $encaisses)
                ],
                'appels' => $appels // 🟢 RJ3NAHA 'appels' bach t-khdem f' l'interface Syndic
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'due_date' => 'required|date'
        ]);

        DB::beginTransaction();
        try {
            $propId = $this->getProprieteId($request);
            if (!$propId) return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
            
            $exCol = $this->col('exercices', 'propriete_id', 'sp_identifier');
            
            $exercice = DB::table('exercices')->whereRaw("{$exCol}::text = ?", [(string)$propId])->where('status', 'en cours')->first();
            if (!$exercice) {
                $exercice = DB::table('exercices')->whereRaw("{$exCol}::text = ?", [(string)$propId])->orderBy('start_date', 'desc')->first();
                if (!$exercice) throw new \Exception("Aucun exercice trouvé pour cette résidence.");
            }

            $af_id = 'AF-EX-' . time();
            $insertAppel = [
                'af_identifier' => $af_id,
                'se_identifier' => $exercice->se_identifier,
                'type_charge' => 'previsionnel',
                'sub_type' => 'exceptionnel',
                'title' => $request->title,
                'amount' => $request->amount,
                'due_date' => $request->due_date,
            ];
            
            $cleCol = $this->col('cle_repartitions', 'propriete_id', 'sp_identifier');
            $cle = DB::table('cle_repartitions')->whereRaw("{$cleCol}::text = ?", [(string)$propId])->first();
            if ($cle) $insertAppel['cle_repartition_id'] = $cle->id;
            
            if (Schema::hasColumn('appels_fonds', 'is_generated')) $insertAppel['is_generated'] = 1;
            if (Schema::hasColumn('appels_fonds', 'is_sent')) $insertAppel['is_sent'] = 1;
            if (Schema::hasColumn('appels_fonds', 'created_at')) $insertAppel['created_at'] = now();
            if (Schema::hasColumn('appels_fonds', 'updated_at')) $insertAppel['updated_at'] = now();

            DB::table('appels_fonds')->insert($insertAppel);

            $cleIdCol = $this->col('unit_to_key', 'cle_repartition_id', 'key_id');
            $queryTotal = DB::table('unit_to_key')
                ->join('units', 'unit_to_key.unit_id', '=', 'units.id')
                ->whereRaw("units.propriete_id::text = ?", [(string)$propId]);
            
            if ($cle) $queryTotal->where("unit_to_key.{$cleIdCol}", $cle->id);
            $totalTantiemes = $queryTotal->sum('unit_to_key.tantieme');

            if ($totalTantiemes <= 0) $totalTantiemes = 10000;

            $propOwnerCol = $this->col('user_as_owner', 'propriete_id', 'sp_identifier');
            $balanceCol = Schema::hasColumn('user_as_owner', 'balance_prev') ? 'balance_prev' : 'solde';

            $owners = DB::table('user_as_owner')->whereRaw("{$propOwnerCol}::text = ?", [(string)$propId])->get();

            foreach ($owners as $owner) {
                $queryUserTantiemes = DB::table('user_owner_unit')
                    ->join('unit_to_key', 'user_owner_unit.unit_id', '=', 'unit_to_key.unit_id')
                    ->whereRaw("user_owner_unit.user_id::text = ?", [(string)$owner->user_id]);
                    
                if ($cle) $queryUserTantiemes->where("unit_to_key.{$cleIdCol}", $cle->id);
                
                $userTantiemes = $queryUserTantiemes->sum('unit_to_key.tantieme');
                if ($userTantiemes <= 0) continue;

                $montantDu = round(($request->amount * $userTantiemes) / $totalTantiemes, 2);
                if ($montantDu <= 0) continue;

                $soldeActuel = (float) ($owner->$balanceCol ?? 0);

                DB::table('appf_to_owner')->insert([
                    'af_identifier' => $af_id,
                    'user_id' => $owner->user_id,
                    'montant_du' => $montantDu,
                    'solde_avant' => $soldeActuel, 
                    'created_at' => now(),
                ]);

                DB::table('user_as_owner')
                    ->whereRaw("user_id::text = ?", [(string)$owner->user_id])
                    ->whereRaw("{$propOwnerCol}::text = ?", [(string)$propId])
                    ->update([$balanceCol => $soldeActuel + $montantDu]); 
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Appel de fonds créé et réparti avec succès.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $appel = DB::table('appels_fonds')->where('af_identifier', $id)->first();
            if (!$appel) return response()->json(['success' => false, 'message' => 'Introuvable'], 404);

            $userNameCol = $this->col('users', 'full_name', 'name');

            $lignes = DB::table('appf_to_owner')
                ->join('users', DB::raw('users.id::text'), '=', DB::raw('appf_to_owner.user_id::text'))
                ->join('user_owner_unit', DB::raw('user_owner_unit.user_id::text'), '=', DB::raw('users.id::text'))
                ->join('units', DB::raw('units.id::text'), '=', DB::raw('user_owner_unit.unit_id::text'))
                ->whereRaw('appf_to_owner.af_identifier::text = ?', [(string)$id])
                ->select(
                    'units.numero_porte as lot_id',
                    "users.{$userNameCol} as owner_name",
                    'appf_to_owner.montant_du as montant',
                    'appf_to_owner.user_id'
                )
                ->get();

            $formattedLignes = $lignes->map(function ($ligne) {
                $solde = (float) (DB::table('user_as_owner')->whereRaw("user_id::text = ?", [(string)$ligne->user_id])->value('balance_prev') ?? 0);
                
                $status = ($solde <= 0) ? 'À jour' : 'En retard';

                return [
                    'id' => $ligne->lot_id,
                    'owner' => $ligne->owner_name,
                    'amount' => $ligne->montant,
                    'status' => $status
                ];
            });

            return response()->json([
                'success' => true,
                'appel' => $appel,
                'lignes' => $formattedLignes
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function downloadPdf(Request $request, $chargeId)
    {
        try {
            $user = $request->attributes->get('gateway_user') ?? $request->user();

            $appel = DB::table('appels_fonds')->where('af_identifier', $chargeId)->first();

            if (!$appel) {
                return response()->json(['message' => 'Appel de charges introuvable.'], 404);
            }

            $ligneCopro = DB::table('appf_to_owner')
                ->where('af_identifier', $chargeId)
                ->whereRaw("user_id::text = ?", [(string)$user->id])
                ->first();

            $data = [
                'title' => $appel->title,
                'amount' => $ligneCopro ? $ligneCopro->montant_du : $appel->amount,
                'due_date' => $appel->due_date,
                'date_emission' => $appel->created_at,
                'user_name' => $user->full_name ?? $user->name ?? 'Copropriétaire',
            ];

            $pdf = Pdf::loadView('pdf.appel_charge', $data);

            return $pdf->download("Appel_Fonds_{$chargeId}.pdf");

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la génération du PDF: ' . $e->getMessage()], 500);
        }
    }
}