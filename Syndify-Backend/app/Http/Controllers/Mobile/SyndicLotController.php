<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SyndicLotController extends Controller
{
    private function col($table, $col, $fallback) {
        return Schema::hasColumn($table, $col) ? $col : $fallback;
    }

    private function getProprieteId($user) {
        $ownerCol = $this->col('user_as_owner', 'propriete_id', 'sp_identifier');
        $propCol  = $this->col('proprietes', 'sp_identifier', 'id');

        $link = DB::table('user_as_owner')
            ->join('proprietes', DB::raw("user_as_owner.{$ownerCol}::text"), '=', DB::raw("proprietes.{$propCol}::text"))
            ->whereRaw("user_as_owner.user_id::text = ?", [$user->id])
            ->where('user_as_owner.role', 'syndic')
            ->where('user_as_owner.status', 1)
            ->select("proprietes.{$propCol} as id")
            ->first();

        return $link ? $link->id : null;
    }

    private function syncOwner($propId, $lotId, $ownerName, $ownerPhone, $ownerEmail) {
        $ownerName = trim($ownerName);
        if (empty($ownerName) || in_array($ownerName, ['Sans propriétaire', 'Inconnu'])) {
            DB::table('user_owner_unit')->whereRaw("unit_id::text = ?", [$lotId])->delete();
            return;
        }

        $ownerEmail = empty(trim($ownerEmail)) ? null : trim($ownerEmail);
        $ownerPhone = empty(trim($ownerPhone)) ? null : trim($ownerPhone);

        $userNameCol = $this->col('users', 'full_name', 'name');
        $ownerCol = $this->col('user_as_owner', 'propriete_id', 'sp_identifier');
        $userId = null;
        
        if ($ownerEmail) {
            $user = DB::table('users')->where('email', $ownerEmail)->first();
            if ($user) $userId = $user->id;
        }
        if (!$userId) {
            $user = DB::table('users')->where($userNameCol, $ownerName)->first();
            if ($user) $userId = $user->id;
        }

        if ($userId) {
            DB::table('users')->whereRaw("id::text = ?", [$userId])->update([
                $userNameCol => $ownerName,
                'tel' => $ownerPhone,
                'email' => $ownerEmail,
                'updated_at' => now()
            ]);
        } else {
            $userId = (string) Str::uuid();
            $userData = [
                'id' => $userId,
                $userNameCol => $ownerName,
                'email' => $ownerEmail,
                'tel' => $ownerPhone,
            ];
            if (Schema::hasColumn('users', 'created_at')) $userData['created_at'] = now();
            if (Schema::hasColumn('users', 'updated_at')) $userData['updated_at'] = now();
            
            try { 
                DB::table('users')->insert($userData); 
            } catch (\Exception $e) { 
                unset($userData['id']); 
                $userId = DB::table('users')->insertGetId($userData); 
            }
        }

        $resLink = DB::table('user_as_owner')
            ->whereRaw("user_id::text = ?", [$userId])
            ->whereRaw("{$ownerCol}::text = ?", [$propId])
            ->first();
            
        if (!$resLink) {
            $insertAsOwner = ['user_id' => $userId, $ownerCol => $propId, 'status' => 1];
            if (Schema::hasColumn('user_as_owner', 'role')) $insertAsOwner['role'] = 'coproprietaire';
            if (Schema::hasColumn('user_as_owner', 'created_at')) $insertAsOwner['created_at'] = now();
            if (Schema::hasColumn('user_as_owner', 'updated_at')) $insertAsOwner['updated_at'] = now();
            DB::table('user_as_owner')->insert($insertAsOwner);
        }

        DB::table('user_owner_unit')->whereRaw("unit_id::text = ?", [$lotId])->delete();
        
        $linkData = ['user_id' => $userId, 'unit_id' => $lotId, 'status' => 1];
        if (Schema::hasColumn('user_owner_unit', 'created_at')) $linkData['created_at'] = now();
        if (Schema::hasColumn('user_owner_unit', 'updated_at')) $linkData['updated_at'] = now();
        
        DB::table('user_owner_unit')->insert($linkData);
    }

    private function syncTantiemes($propId, $lotId, $tantiemes) {
        $cleCol = $this->col('cle_repartitions', 'propriete_id', 'sp_identifier');
        $cleIdCol = $this->col('unit_to_key', 'cle_repartition_id', 'key_id');

        $cle = DB::table('cle_repartitions')->whereRaw("{$cleCol}::text = ?", [$propId])->first();
        if ($cle) {
            $exists = DB::table('unit_to_key')->whereRaw("unit_id::text = ?", [$lotId])->where($cleIdCol, $cle->id)->first();
            if ($exists) {
                DB::table('unit_to_key')->where('id', $exists->id)->update(['tantieme' => $tantiemes ?: 0]);
            } else {
                DB::table('unit_to_key')->insert([
                    'unit_id' => $lotId,
                    $cleIdCol => $cle->id,
                    'tantieme' => $tantiemes ?: 0,
                ]);
            }
        }
    }

    public function index(Request $request)
    {
        try {
            $propId = $this->getProprieteId($request->user());
            if (!$propId) return response()->json(['success' => false, 'message' => 'Aucune copropriété assignée.'], 403);

            $unitCol = $this->col('units', 'propriete_id', 'sp_identifier');
            $userNameCol = $this->col('users', 'full_name', 'name');

            $lots = DB::table('units')->whereRaw("{$unitCol}::text = ?", [$propId])->orderBy('id', 'desc')->get();

            // 🟢 Zidna $propId f l-use bash n9edrou nkhdmou bih lda5el
            $formattedLots = $lots->map(function ($lot) use ($userNameCol, $propId) {
                $owner = DB::table('users')
                    ->join('user_owner_unit', function($join) {
                        $join->on(DB::raw('users.id::text'), '=', DB::raw('user_owner_unit.user_id::text'));
                    })
                    ->where('user_owner_unit.unit_id', $lot->id)
                    // 🟢 Zidna 'users.id as user_id' bash n-jibou l-id dyal l-owner
                    ->select('users.id as user_id', 'users.'.$userNameCol.' as nom', 'users.email', 'users.tel', 'users.photo') 
                    ->first();

                $tantiemes = DB::table('unit_to_key')->where('unit_id', $lot->id)->value('tantieme') ?? 100;

                // Gérer l'URL de la photo
                $photoUrl = null;
                if ($owner && !empty($owner->photo)) {
                    $photoUrl = $owner->photo; 
                }

                // 🟢 L-LOGIC DYAL L-STATUT FINANCIER
                $status = 'À jour'; 
                
                if ($owner && isset($owner->user_id)) {
                    $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
                    
                    // Njibou l-balance dyal l-owner
                    $balancePrev = DB::table('user_as_owner')
                        ->whereRaw("user_id::text = ?", [$owner->user_id])
                        ->whereRaw("{$propOwnerCol}::text = ?", [$propId])
                        ->value('balance_prev');

                    // Ila kan m-sall flouss (> 0), n-reddouh Impayé
                    if ($balancePrev !== null && (float)$balancePrev > 0) {
                        $status = 'Impayé'; 
                    }
                }

                return [
                    'id' => $lot->numero_porte ?? 'Inconnu', 
                    'owner' => $owner ? $owner->nom : 'Sans propriétaire',
                    'floor' => $lot->etage ?? 'RDC',
                    'tantiemes' => $tantiemes, 
                    'type' => $lot->type ?? 'Appartement',
                    'status' => $status, // 🟢 Hna wllina kan-siftou l-status l-m7soub
                    'telephone' => $owner->tel ?? '',
                    'email' => $owner->email ?? '',
                    'db_id' => $lot->id,
                    'photo' => $photoUrl 
                ];
            });

            return response()->json(['success' => true, 'data' => $formattedLots], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur Index: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction(); 
        try {
            $propId = $this->getProprieteId($request->user());
            if (!$propId) return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);

            $unitCol = $this->col('units', 'propriete_id', 'sp_identifier');

            $insertData = [
                $unitCol => $propId,
                'type' => $request->type ?? 'Appartement', 
                'numero_porte' => $request->numero_lot, 
                'etage' => $request->etage,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('units', 'surface')) $insertData['surface'] = $request->surface ?? 0;
            if (Schema::hasColumn('units', 'notes')) $insertData['notes'] = $request->notes;

            $newLotId = DB::table('units')->insertGetId($insertData);

            $this->syncTantiemes($propId, $newLotId, $request->tantiemes);
            $this->syncOwner($propId, $newLotId, $request->proprietaire, $request->telephone, $request->email);

            DB::commit(); 
            return response()->json(['success' => true, 'message' => 'Lot ajouté avec succès.'], 201);
        } catch (\Exception $e) {
            DB::rollBack(); 
            return response()->json(['success' => false, 'message' => 'Erreur Store: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $propId = $this->getProprieteId($request->user());
            
            $lot = DB::table('units')->whereRaw("id::text = ?", [$id])->first();
            if (!$lot) return response()->json(['success' => false, 'message' => 'Lot introuvable.'], 404);

            $updateData = [
                'type' => $request->type ?? 'Appartement', 
                'numero_porte' => $request->numero_lot, 
                'etage' => $request->etage,
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('units', 'surface')) $updateData['surface'] = $request->surface ?? 0;
            if (Schema::hasColumn('units', 'notes')) $updateData['notes'] = $request->notes;

            DB::table('units')->whereRaw("id::text = ?", [$id])->update($updateData);

            $this->syncTantiemes($propId, $id, $request->tantiemes);
            $this->syncOwner($propId, $id, $request->proprietaire, $request->telephone, $request->email);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Lot modifié avec succès.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur Update: ' . $e->getMessage()], 500);
        }
    }

 
}