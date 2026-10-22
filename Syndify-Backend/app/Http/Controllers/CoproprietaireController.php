<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class CoproprietaireController extends Controller
{
    // 🟢 Helpers باش الكود يخدم مع أي سمية ديال Colonne عندك فـ Base de données
    private function getPropCol() { return Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id'; }
    private function getOwnerCol() { return Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier'; }
    private function getUnitCol() { return Schema::hasColumn('units', 'propriete_id') ? 'propriete_id' : 'sp_identifier'; }
    private function getExCol() { return Schema::hasColumn('exercices', 'propriete_id') ? 'propriete_id' : 'sp_identifier'; }

    // 🟢 قادينا هاد الدالة باش تولي تقرا الإقامة من الـ Payload ديال الـ Gateway
    private function getProprieteId(Request $request)
    {
        $user = $request->attributes->get('gateway_user');
        $payload = $request->attributes->get('gateway_payload');

        if (!$user) return null; 

        $userId = $user->id;
        $propOwnerCol = $this->getOwnerCol();

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
    // 1. Liste des Copropriétaires
    // ==========================================
    public function liste(Request $request)
    {
        $payload = $request->attributes->get('gateway_payload')['data'] ?? [];
        $type_affichage = $payload['type'] ?? $payload['type_affichage'] ?? 'tous';

        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return ['data' => ['success' => false, 'message' => 'Accès refusé.'], 'status' => 403];

        $ownerCol = $this->getOwnerCol();
        $unitCol  = $this->getUnitCol();
        $propCol  = $this->getPropCol();

        $residence = DB::table('proprietes')->where($propCol, $propriete_id)->first();

        // 🟢 FIX: ila l-user 3ando ktar mn ligne l-nafss résidence, khud ghi l-a5ira (MAX id)
        $latestLinks = DB::table('user_as_owner')
            ->where($ownerCol, $propriete_id)
            ->groupBy('user_id')
            ->select('user_id', DB::raw('MAX(id) as latest_id'));

        $query = DB::table('users')
            ->joinSub($latestLinks, 'latest_links', function ($join) {
                $join->on('users.id', '=', 'latest_links.user_id');
            })
            ->join('user_as_owner', 'user_as_owner.id', '=', 'latest_links.latest_id');

        $user = $request->attributes->get('gateway_user');
        $query->where('users.id', '!=', $user->id);

        if (Schema::hasColumn('user_as_owner', 'role')) {
            $query->where(function ($q) {
                $q->where('user_as_owner.role', 'not like', '%syndic%')
                  ->orWhereNull('user_as_owner.role');
            });
        }

        if ($type_affichage !== 'tous') {
            $statusMapping = ['en_attente' => 0, 'actif' => 1, 'inactif' => 2];
            if (isset($statusMapping[$type_affichage])) {
                $query->where('user_as_owner.status', $statusMapping[$type_affichage]);
            }
        }

        $users = $query->select('users.*', 'user_as_owner.status as pivot_status')->get();
        $formattedData = $users->map(function ($u) use ($propriete_id, $unitCol) {
            $lotIds = DB::table('user_owner_unit')
                ->join('units', 'user_owner_unit.unit_id', '=', 'units.id')
                ->where("units.$unitCol", $propriete_id)
                ->where('user_owner_unit.user_id', $u->id)
                ->pluck('units.id')
                ->toArray();

            $statusStr = 'Actif';
            if ($u->pivot_status == 0) $statusStr = 'En attente';
            if ($u->pivot_status == 2) $statusStr = 'Inactif';

            return [
                'user_id' => $u->id,
                'nom'     => $u->full_name,
                'email'   => $u->email,
                'tel'     => $u->tel,
                'lots'    => count($lotIds),
                'lot_ids' => $lotIds,
                'status'  => $statusStr
            ];
        });

        return [
            'data' => [
                'success' => true,
                'residence' => [
                    'nom'     => $residence->nom     ?? 'Résidence',
                    'adresse' => $residence->address ?? 'Adresse non définie'
                ],
                'data'          => $formattedData,
                'is_there_more' => false
            ],
            'status' => 200
        ];
    }

    // ==========================================
    // 2. Supprimer un Copropriétaire
    // ==========================================
    public function supprimer(Request $request)
    {
        $payload = $request->attributes->get('gateway_payload')['data'] ?? [];
        if (empty($payload['user_id'])) return ['data' => ['success' => false, 'message' => 'User ID requis.'], 'status' => 400];
        
        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return ['data' => ['success' => false, 'message' => 'Accès refusé.'], 'status' => 403];

        $ownerCol = $this->getOwnerCol();
        $unitCol = $this->getUnitCol();

        DB::beginTransaction();
        try {
            DB::table('user_as_owner')->where('user_id', $payload['user_id'])->where($ownerCol, $propriete_id)->delete(); 
            
            $unitIds = DB::table('units')->where($unitCol, $propriete_id)->pluck('id')->toArray(); 
            if (!empty($unitIds)) {
                DB::table('user_owner_unit')->where('user_id', $payload['user_id'])->whereIn('unit_id', $unitIds)->delete();
            }
            
            DB::commit();
            return ['data' => ['success' => true, 'message' => 'Copropriétaire supprimé avec succès.'], 'status' => 200];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['data' => ['success' => false, 'message' => $e->getMessage()], 'status' => 500];
        }
    }

    // ==========================================
    // 3. Ajouter / Modifier un Copropriétaire
    // ==========================================
    public function ajouter(Request $request)
    {
        $payload = $request->attributes->get('gateway_payload')['data'] ?? [];

        if (empty($payload['nom']) || !isset($payload['status'])) {
            return ['data' => ['success' => false, 'message' => 'Nom et status obligatoires.'], 'status' => 400];
        }

        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return ['data' => ['success' => false, 'message' => 'Accès refusé.'], 'status' => 403];

        $ownerCol = $this->getOwnerCol();
        $unitCol = $this->getUnitCol();

        $mappedStatus = ['En attente' => 0, 'Actif' => 1, 'Inactif' => 2][$payload['status']] ?? 1;

        DB::beginTransaction();
        try {
            $userId = $payload['user_id'] ?? null;
            
            // 1. Création ou Update de l'utilisateur
            if (!$userId) {
                $existingUser = DB::table('users')->where('email', $payload['email'])->first();
                if ($existingUser && !empty($payload['email'])) {
                    $userId = $existingUser->id;
                    DB::table('users')->where('id', $userId)->update([
                        'full_name' => $payload['nom'], 'tel' => $payload['tel'] ?? null, 'updated_at' => now()
                    ]);
                } else {
                   $userId = (string) \Illuminate\Support\Str::uuid();
                    DB::table('users')->insert([
                        'id' => $userId,
                        'full_name' => $payload['nom'], 
                        'email' => $payload['email'] ?? null,
                        'tel' => $payload['tel'] ?? null, 
                        'created_at' => now(), 
                        'updated_at' => now()
                    ]);
                }
            } else {
                DB::table('users')->where('id', $userId)->update([
                    'full_name' => $payload['nom'], 'tel' => $payload['tel'] ?? null, 'updated_at' => now()
                ]);
            }

            // 2. Liaison avec la Résidence
            $liaison = DB::table('user_as_owner')->where('user_id', $userId)->where($ownerCol, $propriete_id)->first(); 
            if ($liaison) {
                DB::table('user_as_owner')->where('id', $liaison->id)->update(['status' => $mappedStatus, 'updated_at' => now()]);
                $message = 'Le copropriétaire a été mis à jour avec succès.';
            } else {
                $insertData = [
                    'user_id' => $userId, 
                    $ownerCol => $propriete_id, 
                    'status' => $mappedStatus, 
                    'created_at' => now(), 
                    'updated_at' => now()
                ];
                if (Schema::hasColumn('user_as_owner', 'role')) {
                    $insertData['role'] = 'coproprietaire';
                }
                
                DB::table('user_as_owner')->insert($insertData);
                $message = 'Copropriétaire ajouté avec succès.';
            }

            // 3. Affectation des Lots
            $unitIds = DB::table('units')->where($unitCol, $propriete_id)->pluck('id')->toArray(); 
            
            if (!empty($unitIds)) {
                DB::table('user_owner_unit')->where('user_id', $userId)->whereIn('unit_id', $unitIds)->delete();
            }

            if (isset($payload['selectedLots']) && is_array($payload['selectedLots']) && count($payload['selectedLots']) > 0) {
                DB::table('user_owner_unit')->whereIn('unit_id', $payload['selectedLots'])->delete();

                $insertData = [];
                foreach ($payload['selectedLots'] as $lotId) {
                    $insertData[] = [
                        'user_id' => $userId, 'unit_id' => $lotId, 'status' => 1, 
                        'created_at' => now(), 'updated_at' => now()
                    ];
                }
                DB::table('user_owner_unit')->insert($insertData);
            }

            DB::commit();
            return ['data' => ['success' => true, 'message' => $message], 'status' => 200];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['data' => ['success' => false, 'message' => $e->getMessage()], 'status' => 500];
        }
    }

    // ==========================================
    // 4. Historique Copropriétaire
    // ==========================================
    public function historique(Request $request)
    {
        $payload = $request->attributes->get('gateway_payload')['data'] ?? [];
        if (empty($payload['user_id'])) return ['data' => ['success' => false, 'message' => 'User ID requis.'], 'status' => 400];

        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return ['data' => ['success' => false, 'message' => 'Accès refusé.'], 'status' => 403];

        $ownerCol = $this->getOwnerCol();
        $exCol    = $this->getExCol();
        $propCol  = $this->getPropCol();

        $user_id = (string) $payload['user_id'];

        // 1. RÉSIDENCE
        $residence = DB::table('proprietes')->where($propCol, $propriete_id)->first();

        // 2. CHECK COPROPRIÉTAIRE
        $userStatusData = DB::table('user_as_owner')
            ->where('user_id', $user_id)
            ->where($ownerCol, $propriete_id)
            ->value('status');

        if ($userStatusData === null) {
            return ['data' => ['success' => false, 'message' => 'Copropriétaire introuvable.'], 'status' => 404];
        }

        // 3. INFO COPROPRIÉTAIRE
        $statusStr = 'Actif';
        if ($userStatusData == 0) $statusStr = 'En attente';
        if ($userStatusData == 2) $statusStr = 'Inactif';

        $copro = DB::table('users')
            ->where('id', $user_id)
            ->select('id', 'full_name as nom', 'email')
            ->first();

        if ($copro) {
            $copro->status = $statusStr;
        }

        // 4. EXERCICES
        $exercices = DB::table('exercices')
            ->where($exCol, $propriete_id)
            ->orderBy('start_date', 'desc')
            ->get();

        if ($exercices->isEmpty()) {
            return ['data' => ['success' => false, 'message' => 'Aucun exercice trouvé.'], 'status' => 404];
        }

        $se_id = $payload['se_identifier'] ?? $exercices->first()->se_identifier;

        $exercicesFormat = $exercices->map(function ($ex) {
            return [
                'id'        => $ex->se_identifier,
                'dateDebut' => $ex->start_date,
                'dateFin'   => $ex->end_date,
                'annee'     => $ex->start_date ? date('Y', strtotime($ex->start_date)) : 'N/A'
            ];
        });

        // 5. OPÉRATIONS
        $encaissements = DB::table('encaissements')
            ->where('se_identifier', $se_id)
            ->where('owner_id', $user_id) 
            ->select(
                'date',
                DB::raw("'Encaissement' as type"),
                'title as description',
                'amount as montant',
                'document_url'
            )
            ->get();

        $appelsFonds = DB::table('appf_to_owner')
            ->join('appels_fonds', 'appf_to_owner.af_identifier', '=', 'appels_fonds.af_identifier')
            ->where('appels_fonds.se_identifier', $se_id)
            ->whereRaw("appf_to_owner.user_id::text = ?", [$user_id]) 
            ->select(
                'appels_fonds.due_date as date',
                DB::raw("'Appel de fonds' as type"),
                'appels_fonds.title as description',
                'appf_to_owner.montant_du as montant',
                DB::raw("null as document_url")
            )
            ->get();

        $depenses = DB::table('depense_for_owner')
            ->join('depenses', 'depense_for_owner.depense_id', '=', 'depenses.id')
            ->where('depenses.se_identifier', $se_id)
            ->whereRaw("depense_for_owner.user_id::text = ?", [$user_id]) 
            ->select(
                'depenses.date',
                DB::raw("'Dépense' as type"),
                'depenses.title as description',
                'depense_for_owner.amount_due as montant',
                DB::raw("null as document_url")
            )
            ->get();

        $operations = collect($encaissements)
            ->merge($appelsFonds)
            ->merge($depenses)
            ->sortByDesc('date')
            ->values();

        $totalMotalabat    = collect($appelsFonds)->sum('montant') + collect($depenses)->sum('montant');
        $totalEncaissements = collect($encaissements)->sum('montant');

        // 6. RESPONSE
        return [
            'data' => [
                'success' => true,
                'residence' => [
                    'nom'     => $residence->nom     ?? 'Résidence Inconnue',
                    'adresse' => $residence->address ?? 'Adresse non définie'
                ],
                'data' => [
                    'coproprietaire'      => $copro,
                    'exercices'           => $exercicesFormat,
                    'exercice_selectionne' => $se_id,
                    'total_encaissements' => $totalEncaissements,
                    'total_depenses'      => $totalMotalabat,
                    'operations'          => $operations
                ]
            ],
            'status' => 200
        ];
    }
}