<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash; // 🟢 ضرورية باش نڤيريفيو المودباس
use Illuminate\Support\Facades\Auth; // 🟢 ضرورية للـ Auth

class AuthController extends Controller
{
    // ==========================================
    // 0. Login (Pour l'application Flutter)
    // ==========================================
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();
        
        // كنتأكدو واش الإيميل كاين وواش المودباس صحيح
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Les identifiants sont incorrects.'
            ], 401);
        }

        // كنكرييو Token جديد للـ Mobile App
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user
        ], 200);
    }

    // ==========================================
    // 9. Data pour la page Home 
    // ==========================================
    public function getHomeData(Request $request)
    {
        // 1. القراءة الآمنة لـ Payload (salma)
        $payloadStr = $request->input('payload');
        $payload = is_string($payloadStr) ? json_decode($payloadStr, true) : $payloadStr;

        if (!is_array($payload)) {
            $content = json_decode($request->getContent(), true);
            $payloadStr2 = (is_array($content) && isset($content['payload'])) ? $content['payload'] : null;
            $payload = is_string($payloadStr2) ? json_decode($payloadStr2, true) : $payloadStr2;
        }

        // 2. حماية صارمة: كنتأكدو بلي Payload عبارة عن Array وفيه الإيميل
        if (!is_array($payload) || !isset($payload['data']['user']['email'])) {
            return [
                'data' => [
                    'message' => "Erreur de format: Payload vide ou mal formaté par le Gateway.",
                    'debug_request' => $request->all(),
                    'debug_content' => $request->getContent()
                ], 
                'status' => 401
            ];
        }

        $email = $payload['data']['user']['email'];

        // 3. نجبدو l'User local
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'data' => ['message' => "Utilisateur non trouvé dans la base locale"], 
                'status' => 404
            ];
        }

        $ownerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        $propCol  = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
        $unitCol  = Schema::hasColumn('units', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        $mesLiaisons = DB::table('user_as_owner')
            ->where('user_id', $user->id)
            ->get();

        $mesProprietesIds = $mesLiaisons->pluck($ownerCol)->toArray();

        $mesProprietes = DB::table('proprietes')
            ->whereIn($propCol, $mesProprietesIds)
            ->orderBy('created_at', 'desc')
            ->get();

        $mesLiaisonsGrouped = $mesLiaisons->groupBy($ownerCol);

        $coproprietes = $mesProprietes->map(function ($prop) use ($user, $mesLiaisonsGrouped, $ownerCol, $propCol, $unitCol) {
            $propId = $prop->$propCol;
            $userLinks = $mesLiaisonsGrouped->get($propId);
            
            $statusStr = 'inactif';
            $rolesStr = 'Inactif';

            if ($userLinks) {
                $activeLinks = $userLinks->where('status', 1);
                $pendingLinks = $userLinks->where('status', 0);
                
                if ($activeLinks->isNotEmpty()) {
                    $statusStr = 'active';
                    $rolesStr = $activeLinks->pluck('role')->filter()->map(fn($r) => ucfirst(strtolower($r)))->unique()->implode(', ');
                    if (empty($rolesStr)) $rolesStr = 'Copropriétaire';
                } elseif ($pendingLinks->isNotEmpty()) {
                    $statusStr = 'en_attente';
                    $rolesStr = 'En attente';
                }
            }

            $syndics = DB::table('user_as_owner')
                ->join('users', 'user_as_owner.user_id', '=', 'users.id')
                ->where("user_as_owner.$ownerCol", $propId)
                ->where('user_as_owner.role', 'syndic')
                ->where('user_as_owner.status', 1) 
                ->select('users.id', 'users.full_name as nom', 'users.email', 'users.tel')
                ->get()
                ->map(function ($s) use ($user) {
                    $s->estVous = ($s->id === $user->id);
                    return $s;
                });

            $coproprietairesList = DB::table('user_as_owner')
                ->join('users', 'user_as_owner.user_id', '=', 'users.id')
                ->where("user_as_owner.$ownerCol", $propId)
                ->where('user_as_owner.status', 1) 
                ->select('users.id', 'users.full_name as nom', 'users.email', 'users.tel')
                ->get()
                ->map(function ($c) use ($user) {
                    $c->estVous = ($c->id === $user->id); 
                    return $c;
                });

            $gerePar = '---';
            if ($syndics->isNotEmpty()) {
                $gerePar = $syndics->map(fn($s) => $s->estVous ? $s->nom . ' (vous)' : $s->nom)->implode(', ');
            }

            $nbLots = DB::table('units')->where($unitCol, $propId)->count();
            $dateFormatee = $prop->created_at ? \Carbon\Carbon::parse($prop->created_at)->translatedFormat('d M, Y') : '---';

            return [
                'id' => $propId, 
                'nom' => $prop->nom ?? 'Résidence Sans Nom',
                'siret' => $prop->siret ?? '---',
                'dateAjout' => $dateFormatee,
                'localisation' => $prop->ville ? $prop->ville . ', Maroc' : '---',
                'syndics' => $syndics,
                'nbSyndics' => $syndics->count(),
                'gerePar' => $gerePar,
                'coproprietaires_list' => $coproprietairesList,
                'nbCoproprietaires' => $coproprietairesList->count(), 
                'nbLots' => $nbLots,
                'role' => $rolesStr, 
                'status' => $statusStr,
                'hasAccess' => ($statusStr === 'active')
            ];
        });

        $activeUserLinks = $mesLiaisons->where('status', 1);
        $globalRolesArray = $activeUserLinks->pluck('role')->filter()->unique()->map(fn($r) => ucfirst(strtolower($r)))->toArray();
        $globalRolesStr = empty($globalRolesArray) ? 'Aucun rôle' : implode(', ', $globalRolesArray);

        return [
            'data' => [
                'message' => "Données chargées avec succès",
                'success' => true,
                'currentUser' => [
                    'nom' => $user->full_name,
                    'date' => date('d M, Y'),
                    'derniereActivite' => 'Aujourd\'hui',
                    'roles' => $globalRolesStr,
                    'proprietesActives' => $activeUserLinks->unique($ownerCol)->count() . ' Propriété(s)',
                    'abonnement' => 'Active',
                    'version' => '1.12.42'
                ],
                'coproprietes' => $coproprietes->values()
            ],
            'status' => 200
        ];
    }
    
    // ==========================================
    // 10. Modifier une propriété 
    // ==========================================
    public function modifierPropriete(Request $request, $id)
    {
        $payloadStr = $request->input('payload');
        $payload = is_string($payloadStr) ? json_decode($payloadStr, true) : $payloadStr;
        
        if (!is_array($payload)) {
             $content = json_decode($request->getContent(), true);
             $payloadStr2 = (is_array($content) && isset($content['payload'])) ? $content['payload'] : null;
             $payload = is_string($payloadStr2) ? json_decode($payloadStr2, true) : $payloadStr2;
        }

        $email = $payload['data']['user']['email'] ?? null;

        if (!$email) {
            return ['data' => ['message' => "Non autorisé"], 'status' => 401];
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return ['data' => ['message' => "Utilisateur introuvable"], 'status' => 404];
        }

        $rawData = $request->input('data');
        $data = is_string($rawData) ? json_decode($rawData, true) : $rawData;
        
        if (!is_array($data)) {
            $content = json_decode($request->getContent(), true);
            $dataStr = $content['data'] ?? null;
            $data = is_string($dataStr) ? json_decode($dataStr, true) : $dataStr;
        }

        if (empty($data['nom'])) {
            return ['data' => ['message' => "Le nom est obligatoire"], 'status' => 400];
        }

        $propCol = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
        $ownerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        $isSyndic = DB::table('user_as_owner')
            ->where('user_id', $user->id)
            ->where($ownerCol, $id)
            ->where('role', 'syndic')
            ->where('status', 1)
            ->exists();

        if (!$isSyndic) {
            return ['data' => ['message' => "Vous n'avez pas les droits pour modifier cette copropriété."], 'status' => 403];
        }

        try {
            $updateData = [
                'nom' => $data['nom'],
                'address' => $data['adresse'] ?? null,
                'ville' => $data['ville'] ?? null,
                'updated_at' => now()
            ];

            DB::table('proprietes')->where($propCol, $id)->update($updateData);

            return [
                'data' => [
                    'message' => 'Copropriété modifiée avec succès.',
                    'success' => true
                ],
                'status' => 200
            ];

        } catch (\Exception $e) {
            return [
                'data' => ['message' => "Erreur lors de la modification: " . $e->getMessage()], 
                'status' => 500
            ];
        }
    }
    
    // ==========================================
    // 11. Supprimer une propriété 
    // ==========================================
    public function supprimerPropriete(Request $request, $id)
    {
        $payloadStr = $request->input('payload');
        $payload = is_string($payloadStr) ? json_decode($payloadStr, true) : $payloadStr;
        
        if (!is_array($payload)) {
             $content = json_decode($request->getContent(), true);
             $payloadStr2 = (is_array($content) && isset($content['payload'])) ? $content['payload'] : null;
             $payload = is_string($payloadStr2) ? json_decode($payloadStr2, true) : $payloadStr2;
        }

        $email = $payload['data']['user']['email'] ?? null;

        if (!$email) {
            return ['data' => ['message' => "Non autorisé"], 'status' => 401];
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return ['data' => ['message' => "Utilisateur introuvable"], 'status' => 404];
        }

        $propCol = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
        $ownerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        $isSyndic = DB::table('user_as_owner')
            ->where('user_id', $user->id)
            ->where($ownerCol, $id)
            ->where('role', 'syndic')
            ->where('status', 1)
            ->exists();

        if (!$isSyndic) {
            return ['data' => ['message' => "Vous n'avez pas les droits pour supprimer cette copropriété."], 'status' => 403];
        }

        try {
            DB::beginTransaction();
            DB::table('user_as_owner')->where($ownerCol, $id)->delete();
            DB::table('proprietes')->where($propCol, $id)->delete();
            DB::commit();

            return [
                'data' => [
                    'message' => 'Copropriété supprimée avec succès.',
                    'success' => true
                ],
                'status' => 200
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'data' => ['message' => "Erreur lors de la suppression: " . $e->getMessage()], 
                'status' => 500
            ];
        }
    }

    // ==========================================
    // 12. Déconnexion (Logout)
    // ==========================================
    public function logout(Request $request)
    {
        // 🟢 كنمسحو الـ Token باش مايبقاش صالح من مورا الـ Logout
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'data' => [
                'message' => 'Déconnecté avec succès.',
                'success' => true
            ],
            'status' => 200
        ]);
    }
}