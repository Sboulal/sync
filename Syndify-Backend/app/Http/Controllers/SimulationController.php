<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class SimulationController extends Controller
{
// ========================================================
    // 🟢 FONCTION SÉCURISÉE AVEC AUTHENTIFICATION RÉELLE
    // ========================================================
    private function getProprieteId(Request $request)
    {
        $userId = auth()->id(); 
        if (!$userId) return null; 

        $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        // 🟢 1. كنقراو residence_id من (Headers -> Body -> Cookie) بحال LotController
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
  public function chargerDonneesSimulation(Request $request)
    {
        Log::info('--- Simulation : Chargement ---');
        
        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        try {
            // 🟢 1. Jbed l-m3loumat dyal l-Résidence
            $propIdCol_propriete = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
            $residence = DB::table('proprietes')->where($propIdCol_propriete, $sp_id)->first();

            $userPk = Schema::hasColumn('users', 'identifier') ? 'identifier' : 'id';
            $userNameCol = Schema::hasColumn('users', 'full_name') ? 'full_name' : 'name';
            $cleIdCol = Schema::hasColumn('unit_to_key', 'cle_repartition_id') ? 'cle_repartition_id' : 'cle_id';

     // 2. Clés
            $cles = DB::table('cle_repartitions')->where('propriete_id', $sp_id)->get();

            foreach ($cles as $cle) {
                // N-jbdou l-somme dyal les tantièmes l-7a9i9iya mn DB
                $sommeTantiemes = DB::table('unit_to_key')->where($cleIdCol, $cle->id)->sum('tantieme');
                
                $totalTantiemes = (!empty($cle->tantiemes_total) && $cle->tantiemes_total > 0) 
                                    ? $cle->tantiemes_total 
                                    : ($sommeTantiemes > 0 ? $sommeTantiemes : 1000);

                $cle->lots = DB::table('unit_to_key')
                    ->join('units', 'units.id', '=', 'unit_to_key.unit_id')
                    // 🟢 FIX 1: 7iydna condition 'status = 1' bash 7ta copropriétaire ma-y-t-ghber
                    ->leftJoin('user_owner_unit', 'user_owner_unit.unit_id', '=', 'units.id')
                    ->leftJoin('users', 'users.id', '=', 'user_owner_unit.user_id') 
                    ->where('unit_to_key.' . $cleIdCol, $cle->id)
                    ->select(
                        'units.id as lot_id', 
                        'units.type', 
                        'units.numero_porte', 
                        'unit_to_key.tantieme',
                        DB::raw("$totalTantiemes as tantiemes_total"),
                        // 🟢 FIX 2: N-khedmou b users.id l-7a9i9i bash l-groupement f Angular y-khdem 100%
                        'users.id as owner_id', 
                        DB::raw("COALESCE(users.$userNameCol, 'Sans propriétaire') as owner_name")
                    )
                    ->get();
            }

            // 🟢 3. Rjja3 kolchi
            return response()->json([
                'success' => true, 
                'residence' => [
                    'nom' => $residence->nom ?? 'Résidence',
                    'adresse' => $residence->address ?? 'Adresse non définie'
                ],
                'data' => $cles
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}