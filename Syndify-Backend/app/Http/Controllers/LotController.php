<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class LotController extends Controller
{
    // 🟢 قادينا هاد الدالة باش تولي تقرا الإقامة من الـ Payload ديال الـ Gateway
    private function getProprieteId(Request $request)
    {
        $user = $request->attributes->get('gateway_user');
        $payload = $request->attributes->get('gateway_payload');

        if (!$user) return null; 

        $userId = $user->id;
        $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        // 1. كنجبدو ID ديال الإقامة من الـ Payload
        $residenceId = $payload['data']['residence_id'] ?? $request->header('residence_id') ?? $request->input('residence_id');

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
    // 1. Liste des Lots
    // ==========================================
    public function liste(Request $request)
    {
        $propriete_id = $this->getProprieteId($request);
        
        if (!$propriete_id) {
            return ['data' => ['success' => false, 'message' => 'Accès refusé.'], 'status' => 403];
        }

        $propIdCol_propriete = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
        $propIdCol_units     = Schema::hasColumn('units', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        $propOwnerCol        = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        $userNameCol         = Schema::hasColumn('users', 'full_name') ? 'full_name' : 'name';

        $residence = DB::table('proprietes')->where($propIdCol_propriete, $propriete_id)->first();
        $lots = DB::table('units')->where($propIdCol_units, $propriete_id)->orderBy('id', 'desc')->get();

        $formattedLots = [];

        foreach ($lots as $lot) {
            // 1. Njibou l-propriétaire dyal l-lot
            $owner = DB::table('users')
                ->join('user_owner_unit', 'users.id', '=', 'user_owner_unit.user_id')
                ->where('user_owner_unit.unit_id', $lot->id)
                ->select('users.id as user_id', 'users.' . $userNameCol . ' as nom', 'users.email', 'users.tel', 'users.photo')
                ->first();

            // 2. Njibou tantièmes
            $tantiemes = DB::table('unit_to_key')->where('unit_id', $lot->id)->value('tantieme') ?? 100;

            // 3. 🟢 L-LOGIC DYAL L-STATUT FINANCIER
            $status = 'À jour'; 
            
            if ($owner) {
                // Kan-jibou l-balance dyal l-owner mn table user_as_owner
                $userAsOwner = DB::table('user_as_owner')
                    ->where('user_id', (string)$owner->user_id)
                    ->where($propOwnerCol, $propriete_id)
                    ->first();
                
                // Ila kant l-balance kber mn 0, rah 3lih flouss -> Impayé (li ghadi trje3 "En retard" f Flutter)
                if ($userAsOwner && $userAsOwner->balance_prev > 0) {
                    $status = 'Impayé'; 
                }
            }

            // 4. Kan-formatiw l-données kifma baghiha l-application Flutter
            $formattedLots[] = [
                'id' => $lot->numero_porte ?? 'Inconnu', 
                'owner' => $owner ? $owner->nom : 'Sans propriétaire',
                'floor' => $lot->etage ?? 'RDC',
                'tantiemes' => $tantiemes, 
                'type' => $lot->type ?? 'Appartement',
                'status' => $status, // 🟢 Hna kan-siftou l-statut s7i7
                'telephone' => $owner->tel ?? '',
                'email' => $owner->email ?? '',
                'db_id' => $lot->id,
                'photo' => $owner->photo ?? null 
            ];
        }

        return [
            'data' => [
                'success' => true, 
                'residence' => [
                    'nom' => $residence->nom ?? 'Résidence',
                    'adresse' => $residence->address ?? 'Adresse non définie'
                ],
                'data' => $formattedLots
            ],
            'status' => 200
        ];
    }
    // ==========================================
    // 2. Ajouter un lot
    // ==========================================
    public function ajouter(Request $request)
    {
        $payload = $request->attributes->get('gateway_payload')['data'] ?? [];

        if (empty($payload['type']) || empty($payload['numero_porte'])) {
            return ['data' => ['success' => false, 'message' => 'Type et N° Porte obligatoires.'], 'status' => 400];
        }

        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return ['data' => ['success' => false, 'message' => 'Accès refusé.'], 'status' => 403];

        $propIdCol_units = Schema::hasColumn('units', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        $propIdCol_cles  = Schema::hasColumn('cle_repartitions', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        $cleIdCol_pivot  = Schema::hasColumn('unit_to_key', 'cle_repartition_id') ? 'cle_repartition_id' : 'key_id';
        $propOwnerCol    = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        DB::beginTransaction();
        try {
            $lotId = DB::table('units')->insertGetId([
                $propIdCol_units => $propriete_id,
                'type'           => $payload['type'],
                'batiment'       => $payload['batiment'] ?? null,
                'etage'          => $payload['etage'] ?? null,
                'numero_porte'   => $payload['numero_porte'],
                'created_at'     => now(),
                'updated_at'     => now()
            ]);

            try {
                $cles = DB::table('cle_repartitions')->where($propIdCol_cles, $propriete_id)->get();
                $insertKeys = [];
                foreach ($cles as $cle) {
                    $insertKeys[] = [
                        'unit_id'       => $lotId,
                        $cleIdCol_pivot => $cle->id,
                        'tantieme'      => 0,
                        'created_at'    => now(),
                        'updated_at'    => now()
                    ];
                }
                if (!empty($insertKeys)) DB::table('unit_to_key')->insert($insertKeys);
            } catch (\Exception $e) {
                Log::warning("Impossible de lier les clés au lot : " . $e->getMessage());
            }

            if (!empty($payload['owner_id'])) {
                $status = ($payload['owner_status'] ?? 'Actif') == 'Actif' ? 1 : 2;

                if ($status == 1) {
                    DB::table('user_as_owner')
                        ->where('user_id', (string)$payload['owner_id'])
                        ->where($propOwnerCol, $propriete_id)
                        ->update(['status' => 1]);
                }

                DB::table('user_owner_unit')->insert([
                    'user_id'    => (string)$payload['owner_id'],
                    'unit_id'    => $lotId,
                    'status'     => $status,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
            return ['data' => ['success' => true, 'message' => 'Lot ajouté avec succès.'], 'status' => 200];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['data' => ['success' => false, 'message' => $e->getMessage()], 'status' => 500];
        }
    }

    // ==========================================
    // 3. Modifier un Lot
    // ==========================================
    public function modifier(Request $request)
    {
        $payload = $request->attributes->get('gateway_payload')['data'] ?? [];
        
        if (empty($payload['lot_id'])) {
            return ['data' => ['success' => false, 'message' => 'ID Lot manquant.'], 'status' => 400];
        }

        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return ['data' => ['success' => false, 'message' => 'Accès refusé.'], 'status' => 403];

        $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        DB::beginTransaction();
        try {
            DB::table('units')->where('id', $payload['lot_id'])->update([
                'type'         => $payload['type'] ?? 'Appartement',
                'batiment'     => $payload['batiment'] ?? null,
                'etage'        => $payload['etage'] ?? null,
                'numero_porte' => $payload['numero_porte'] ?? null,
                'updated_at'   => now()
            ]);

            DB::table('user_owner_unit')->where('unit_id', $payload['lot_id'])->delete();

            if (!empty($payload['owner_id'])) {
                $status = ($payload['owner_status'] ?? 'Actif') == 'Actif' ? 1 : 2;

                if ($status == 1) { 
                    DB::table('user_as_owner')
                        ->where('user_id', $payload['owner_id'])
                        ->where($propOwnerCol, $propriete_id)
                        ->update(['status' => 1]);
                } 
                elseif ($status == 2) { 
                    DB::table('user_as_owner')
                        ->where('user_id', $payload['owner_id'])
                        ->where($propOwnerCol, $propriete_id)
                        ->update(['balance_prev' => 0, 'balance_trav' => 0, 'status' => 2]); 
                }

                DB::table('user_owner_unit')->insert([
                    'user_id'    => $payload['owner_id'], 
                    'unit_id'    => $payload['lot_id'],
                    'status'     => $status,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
            return ['data' => ['success' => true, 'message' => 'Lot modifié avec succès.'], 'status' => 200];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['data' => ['success' => false, 'message' => $e->getMessage()], 'status' => 500];
        }
    }

    // ==========================================
    // 4. Supprimer un lot
    // ==========================================
    public function supprimer(Request $request)
    {
        $payload = $request->attributes->get('gateway_payload')['data'] ?? [];

        if (empty($payload['lot_id'])) {
            return ['data' => ['success' => false, 'message' => 'ID Lot manquant.'], 'status' => 400];
        }

        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return ['data' => ['success' => false, 'message' => 'Accès refusé.'], 'status' => 403];

        $propIdCol_units = Schema::hasColumn('units', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        try {
            DB::beginTransaction();
            DB::table('user_owner_unit')->where('unit_id', $payload['lot_id'])->delete();
            try { DB::table('unit_to_key')->where('unit_id', $payload['lot_id'])->delete(); } catch(\Exception $e) {}
            
            DB::table('units')->where('id', $payload['lot_id'])->where($propIdCol_units, $propriete_id)->delete();
            
            DB::commit();
            return ['data' => ['success' => true, 'message' => 'Lot supprimé avec succès.'], 'status' => 200];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['data' => ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 'status' => 500];
        }
    }
}