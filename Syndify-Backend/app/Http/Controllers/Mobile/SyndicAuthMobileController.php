<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail; // 🟢 زدت هادي
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;


class SyndicAuthMobileController extends Controller
{
 // ==========================================
    // 1. Connexion (Login avec Email ou Téléphone)
    // ==========================================
    public function login(Request $request)
    {
        $request->validate([
            'identifiant' => 'required',
            'password' => 'required',
        ]);

        $identifiant = $request->identifiant;

        $user = User::where('email', $identifiant)
                    ->orWhere('tel', $identifiant) 
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Les identifiants sont incorrects.'
            ], 401);
        }

        // 🟢 FIX: N-jbdou l-ID w status s7a7 bla ma y-khesserhom Laravel
        $rawUserId = $user->getRawOriginal('id') ?? $user->id;
        $rawUserStatus = $user->getRawOriginal('status') ?? $user->status;

        if ((string)$rawUserStatus === '0') {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est en cours de validation par le syndic. Veuillez patienter.'
            ], 403);
        }

        // 🟢 AUTO-FIX: Ila kan l'user m-validé f users w l-code qdim nssa ma-bdlha f user_as_owner, n-s77oha daba automatique!
        if ((string)$rawUserStatus === '1' || strtolower((string)$rawUserStatus) === 'actif') {
            DB::table('user_as_owner')
                ->where('user_id', $rawUserId)
                ->where('status', 0)
                ->update(['status' => 1]);
        }

        // 1. Jib GA3 les enregistrements dyal had l'user f user_as_owner
       $propRecords = DB::table('user_as_owner')
            ->where('user_id', $rawUserId)
            ->where('status', 1) // 🟢 FIX: PostgreSQL bɛ sɔn nimɔrɔ dɔrɔn ma
            ->get();

        if ($propRecords->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Vous n\'êtes lié à aucune copropriété.'
            ], 403);
        }

        // 2. Kanjbdou ga3 les rôles w kanderouhoum f Tableau (Array)
        $roles = $propRecords->pluck('role')->map(function($r) {
            return strtolower($r);
        })->unique()->values()->toArray();

        // 3. Nakhdou l'appartement lwla (awla d syndic) bach njbdou smit l'copropriété
        $propRecord = $propRecords->first();

        $propOwnerCol = \Illuminate\Support\Facades\Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        $propCol = \Illuminate\Support\Facades\Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
        
        $residence = DB::table('proprietes')->where($propCol, $propRecord->$propOwnerCol)->first();
        $residenceName = $residence ? $residence->nom : 'Votre Résidence';

        $token = $user->createToken('mobile-app')->plainTextToken;
        $photo = $user->photo_url ?? $user->photo ?? $user->avatar ?? null;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'token' => $token,
                'role'  => $roles[0], 
                'roles' => $roles,    
                'residence_name' => $residenceName, 
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->full_name ?? $user->name ?? '',
                    'email' => $user->email,
                    'photo' => $photo,
                ]
            ]
        ], 200);
    }

    // ==========================================
    // INSCRIPTION DU SYNDIC (Création de la Résidence)
    // ==========================================
    public function registerSyndic(Request $request)
    {
        // 1. Validation des données du Syndic
        $request->validate([
            'name' => 'required|string|max:255',
            'tel' => 'required|string|max:20',
            'password' => 'required|string|min:6',
            'nom_residence' => 'required|string|max:255' // 🟢 L-Syndic khasso y-dkhl smit l-i9ama
        ]);

        // 2. Nvériifiw wach had l-Syndic 3ndo compte deja
        $query = User::where('tel', $request->tel);
        if ($request->filled('email')) {
            $query->orWhere('email', $request->email);
        }
        if ($query->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Un compte existe déjà avec ce numéro ou email.'
            ], 400);
        }

        // 3. Création dyal l'user (Syndic)
        $user = new User();
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'full_name')) {
            $user->full_name = $request->name;
        } else {
            $user->name = $request->name;
        }
        $user->tel = $request->tel;
        if ($request->filled('email')) {
            $user->email = $request->email;
        }
        $user->password = Hash::make($request->password);
        $user->status = 1; // 🟢 L-Syndic kaykoun m-validé automatique (status = 1)
        $user->save();

        // 4. Génération dyal l-Code Résidence Unique (ex: SYND-8F4A9Z)
        $codeResidence = 'SYND-' . strtoupper(Str::random(6));

        // 5. Création dyal l-Résidence f table proprietes
        $propId = DB::table('proprietes')->insertGetId([
            'nom' => $request->nom_residence,
            'code_residence' => $codeResidence, // 🟢 Kan-sauvegardiw l-code f DB
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 6. N-linkiw l-Syndic m3a l-Résidence f table user_as_owner
        $ownerCol = \Illuminate\Support\Facades\Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        
        DB::table('user_as_owner')->insert([
            'user_id' => $user->id,
            $ownerCol => $propId,
            'role' => 'syndic', // 🟢 Role: Syndic
            'status' => 1,      // 🟢 Status: M-validé
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Résidence créée avec succès.',
            'code_residence' => $codeResidence, // 🟢 Kan-rej3ou l-code f l-API bach n-werriwh l-Syndic
            'data' => [
                'token' => $user->createToken('mobile-app')->plainTextToken, // Y-dkhol mbsahran
                'role' => 'syndic'
            ]
        ], 201);
    }

    // 🟢 Fonction bach tsifti WhatsApp (Mital b API bhal UltraMsg)
    private function sendWhatsAppMessage($phone, $message)
    {
        // Hna khassk t-7tti l-ma3loumat dyal l-API li ghadi t-khdmi biha
        $instanceId = "instance192404"; 
        $token = "gfsyopd8k24mh4ha";
        $url = "https://api.ultramsg.com/{$instanceId}/messages/chat";

        // Format dyal nmra (khassha tkon b format international, ex: +212600000000)
        // Hna n-gaddouha automatique ila kant b 06 aw 07
        if (strpos($phone, '0') === 0) {
            $phone = '+212' . substr($phone, 1);
        }

        try {
            Http::post($url, [
                'token' => $token,
                'to' => $phone,
                'body' => $message
            ]);
        } catch (\Exception $e) {
            // Log l-erreur ila kan mochkil f connexion m3a WhatsApp
            \Log::error("Erreur WhatsApp: " . $e->getMessage());
        }
    }

   // ==========================================
    // VALIDER UN COPROPRIÉTAIRE (Mise à jour)
    // ==========================================
    public function validerCoproprietaire($id)
    {
        // 🟢 N-khdmou b DB::table direct bach n-tfadaw l-machakil dyal Eloquent Models (Casting ID bug)
        $user = DB::table('users')->where('id', $id)->first();
        
        if($user) {
            // 1. Validation f table users
            DB::table('users')->where('id', $id)->update(['status' => 1]);

            // 2. Validation f table user_as_owner (Darouri!)
            DB::table('user_as_owner')
                ->where('user_id', $id)
                ->update(['status' => 1, 'updated_at' => now()]);

            // 3. Sift WhatsApp dyal l-Validation
            if (!empty($user->tel)) {
                $msgValidation = "Félicitations {$user->full_name} 🎉, \n\nVotre compte *Syndify* a été validé par le syndic ! \n\nVous pouvez maintenant vous connecter à l'application avec votre numéro ou email et votre mot de passe.";
                $this->sendWhatsAppMessage($user->tel, $msgValidation);
            }

            return response()->json(['success' => true, 'message' => 'Utilisateur validé avec succès']);
        }

        return response()->json(['success' => false, 'message' => 'Utilisateur introuvable'], 404);
    }
   // ==========================================
    // INSCRIPTION AVEC CODE RÉSIDENCE
    // ==========================================
    public function register(Request $request)
    {
        // 1. Validation (Zidna code_residence)
        $request->validate([
            'name' => 'required|string|max:255',
            'tel' => 'required|string|max:20',
            'password' => 'required|string|min:6',
            'code_residence' => 'required|string' // 🟢 ZEDNA HADI
        ]);

        // 🟢 2. N-9ellbou 3la l-i9ama b had l-code
        // (T2kdi mn smit l-colonne dyal l-code f table proprietes, hna dartha 'code_residence')
        $residence = DB::table('proprietes')->where('code_residence', $request->code_residence)->first();

        if (!$residence) {
            return response()->json([
                'success' => false,
                'message' => 'Le code de la résidence est invalide.'
            ], 400);
        }

        // 3. Nvériifiw wach had nemra wla l'email déja kaynin
        $query = User::where('tel', $request->tel);
        if ($request->filled('email')) {
            $query->orWhere('email', $request->email);
        }
        $existingUser = $query->first();

        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'Un compte existe déjà avec ce numéro ou email.'
            ], 400);
        }

        // 4. Création dyal l'user jdid
        $user = new User();
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'full_name')) {
            $user->full_name = $request->name;
        } else {
            $user->name = $request->name;
        }
        $user->tel = $request->tel;
        if ($request->filled('email')) {
            $user->email = $request->email;
        }
        $user->password = Hash::make($request->password);
        $user->status = 0; // L'compte bchwya 0
        $user->save();

        // 🟢 5. N-linkiw l-User m3a l-Résidence f table user_as_owner
        $propCol = \Illuminate\Support\Facades\Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
        $ownerCol = \Illuminate\Support\Facades\Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        DB::table('user_as_owner')->insert([
            'user_id' => $user->id,
            $ownerCol => $residence->$propCol,
            'role' => 'coproprietaire',
            'status' => 0, // En attente
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 🟢 6. Sift WhatsApp dyal l-inscription
        $msgInscription = "Bonjour {$user->full_name}, \n\nVotre demande d'inscription à la résidence *{$residence->nom}* a été reçue 🏢.\n\nVotre compte est en attente de validation par votre syndic. \n\nMerci!";
        $this->sendWhatsAppMessage($user->tel, $msgInscription);

        return response()->json([
            'success' => true,
            'message' => 'Demande envoyée. En attente de validation par le syndic.'
        ], 201);
    }
   // ==========================================
    // 2. Récupérer le Profil (Écran 25)
    // ==========================================
    public function profil(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;

        // 🟢 1. كنعرفو المساحة (Espace) اللي داخل منها اليوزر انطلاقاً من الرابط
        $isSyndicSpace = $request->is('*mobile/syndic*');

        // 🟢 2. كنجبدو الـ Record الصحيح على حساب المساحة الحالية
        if ($isSyndicSpace) {
            $propRecord = DB::table('user_as_owner')
                ->where('user_id', $userId)
                ->whereRaw('LOWER(role) = ?', ['syndic'])
                ->first();
            $role = 'syndic';
        } else {
            $propRecord = DB::table('user_as_owner')
                ->where('user_id', $userId)
                ->whereRaw('LOWER(role) != ?', ['syndic']) // أي حاجة من غير سانديك (Coproprietaire)
                ->first();
            $role = 'coproprietaire';
        }

        // حالة نادرة (Fallback) يلا مالقاش البيانات المحددة
        if (!$propRecord) {
            $propRecord = DB::table('user_as_owner')->where('user_id', $userId)->first();
            $role = $propRecord ? strtolower($propRecord->role) : 'coproprietaire';
        }

        $propOwnerCol = \Illuminate\Support\Facades\Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        $residenceId = $request->header('residence_id') ?? ($propRecord ? $propRecord->$propOwnerCol : null);

        $propCol = \Illuminate\Support\Facades\Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
        $residence = DB::table('proprietes')->where($propCol, $residenceId)->first();

        $lots = DB::table('user_owner_unit')
            ->join('units', 'user_owner_unit.unit_id', '=', 'units.id')
            ->where('user_owner_unit.user_id', $userId)
            ->pluck('units.numero_porte')
            ->implode(', ');

        $solde = $propRecord ? (float) ($propRecord->balance_prev ?? 0) : 0;

        // Calcul de la "Prochaine charge"
        $prochaineChargeRecord = DB::table('appf_to_owner')
            ->join('appels_fonds', 'appf_to_owner.af_identifier', '=', 'appels_fonds.af_identifier')
            ->where('appf_to_owner.user_id', $userId)
            ->where('appels_fonds.due_date', '>=', now())
            ->orderBy('appels_fonds.due_date', 'asc')
            ->first();
            
        $prochaineCharge = $prochaineChargeRecord 
            ? "T" . ceil(date('n', strtotime($prochaineChargeRecord->due_date))/3) . " - " . number_format($prochaineChargeRecord->amount, 0) . " MAD" 
            : 'À jour';

        // Calcul du "Dernier impayé" (basé sur le solde et les anciens appels)
        $dernierImpayeDate = 'Aucun';
        if ($solde > 0) { 
            $dernierImpayeRecord = DB::table('appf_to_owner')
                ->join('appels_fonds', 'appf_to_owner.af_identifier', '=', 'appels_fonds.af_identifier')
                ->where('appf_to_owner.user_id', $userId)
                ->where('appels_fonds.due_date', '<', now())
                ->orderBy('appels_fonds.due_date', 'desc')
                ->first();
                
            if ($dernierImpayeRecord) {
                $dernierImpayeDate = \Carbon\Carbon::parse($dernierImpayeRecord->due_date)->format('d/m/Y');
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'nom' => $user->full_name ?? $user->name,
                'email' => $user->email,
                'telephone' => $user->tel ?? 'Non renseigné',
                'role' => $role,
                'lot' => $lots ?: 'Aucun',
                'copropriete' => $residence->nom ?? 'Résidence Inconnue',
                'adresse' => $residence->address ?? 'Non définie',
                'solde_brut' => $solde,
                'solde_formate' => number_format($solde, 2, ',', ' ') . ' MAD',
                'prochaine_charge' => $prochaineCharge,
                'dernier_impaye' => $dernierImpayeDate,
                'photo' => $user->photo_url ?? $user->photo ?? $user->avatar ?? null
            ]
        ], 200);
    }

public function updatePhoto(Request $request)
{
    $request->validate([
        'photo' => 'required|image|mimes:jpeg,png,jpg|max:5120',
    ]);

    $user = $request->user();

    if ($request->hasFile('photo')) {
        $file = $request->file('photo');
        
        // 1. توليد اسم فريد للصورة
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        
        // 2. تسجيل الصورة مباشرة في مجلد public/profils
        // هاد السطر غيكريي الدوسي profils لداخل د public إلى مكانش كاين
        $file->move(public_path('profils'), $filename);
        
        // 3. تكوين الرابط ديال الصورة
        $fullUrl = str_replace('http://', 'https://', asset('profils/' . $filename));
        
        // 4. تسجيل في قاعدة البيانات
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'photo_url')) {
            $user->photo_url = $fullUrl;
        } elseif (\Illuminate\Support\Facades\Schema::hasColumn('users', 'photo')) {
            $user->photo = $fullUrl;
        } else {
            $user->avatar = $fullUrl;
        }
        
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Photo mise à jour avec succès.',
            'photo_url' => $fullUrl 
        ], 200);
    }

    return response()->json(['success' => false, 'message' => 'Aucune image reçue.'], 400);
}
    // ==========================================
    // 3. Modifier le Mot de Passe (Écran 25)
    // ==========================================
    public function updatePassword(Request $request)
    {
        $request->validate([
            'ancien_password' => 'required',
            'nouveau_password' => 'required|min:6'
        ]);

        $user = $request->user();

        if (!Hash::check($request->ancien_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'L\'ancien mot de passe est incorrect.'
            ], 400);
        }

        $user->password = Hash::make($request->nouveau_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe mis à jour avec succès.'
        ], 200);
    }

    // ==========================================
    // 4. Déconnexion (Logout)
    // ==========================================
    public function logout(Request $request)
    {
        // Révocation du Token actuel
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Déconnecté avec succès.'
        ], 200);
    }

    // ==========================================
    // AFFICHER LES COPROPRIÉTAIRES EN ATTENTE
    // ==========================================
    public function getCoproprietairesEnAttente(Request $request)
    {
        $user = $request->user();

        $propOwnerCol = \Illuminate\Support\Facades\Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        $syndicRecord = DB::table('user_as_owner')
            ->where('user_id', $user->id)
            ->whereRaw('LOWER(role) = ?', ['syndic'])
            ->first();

        if (!$syndicRecord) {
            return response()->json(['success' => false, 'message' => 'Syndic non trouvé'], 403);
        }

        // 🟢 Bdellna select bash n-jbdou kolchi (users.*) bla ma n-7eddo les colonnes dyal tsawer li momkin ykono makayninch
        $pendingUsers = DB::table('user_as_owner')
            ->join('users', 'user_as_owner.user_id', '=', 'users.id')
            ->where('user_as_owner.'.$propOwnerCol, $syndicRecord->$propOwnerCol)
            ->where('user_as_owner.status', 0)
            ->select('users.*', 'user_as_owner.created_at as date_demande')
            ->orderBy('user_as_owner.created_at', 'desc')
            ->get();

        $formattedUsers = $pendingUsers->map(function($u) {
            return [
                'id' => $u->id,
                'nom' => $u->full_name ?? $u->name ?? 'Inconnu',
                'tel' => $u->tel ?? '',
                'email' => $u->email ?? '',
                'photo' => $u->photo_url ?? $u->photo ?? $u->avatar ?? null,
                'date_demande' => \Carbon\Carbon::parse($u->date_demande)->format('d/m/Y H:i')
            ];
        });

        return response()->json([
            'success' => true, 
            'data' => $formattedUsers
        ], 200);
    }

    // ==========================================
    // 5. Sauvegarder le Token FCM pour les Notifications
    // ==========================================
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Token FCM mis à jour avec succès.'
        ], 200);
    }

    // ==========================================
    // 6. Envoyer le code de réinitialisation (Mot de passe oublié)
    // ==========================================
    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // 1. واش خونا كاين فلقاعدة د البيانات؟
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun compte trouvé avec cette adresse email.'
            ], 404);
        }

        // 2. إنشاء كود من 5 أرقام
        $code = rand(10000, 99999);

        // 3. تسجيل الكود في قاعدة البيانات (Laravel table par défaut)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $code, // هنا غنخزنو الكود ديال 5 أرقام
                'created_at' => Carbon::now()
            ]
        );

        // 4. إرسال الكود عبر الإيميل
        try {
            Mail::raw("Bonjour, \n\nVotre code de réinitialisation est : $code \n\nCe code est valable pour une courte durée.", function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Sindy : Code de réinitialisation de mot de passe');
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email. Vérifiez la configuration SMTP.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Un code de vérification a été envoyé à votre adresse email.'
        ], 200);
    }

    // ==========================================
    // 7. Vérifier le code et réinitialiser le mot de passe
    // ==========================================
    public function resetPasswordWithCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|numeric|digits:5',
            'nouveau_password' => 'required|min:6'
        ]);

        // 1. التحقق من الكود
        $resetRequest = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->code)
            ->first();

        if (!$resetRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Code de vérification invalide ou expiré.'
            ], 400);
        }

        // 2. تغيير المودباس
        $user = User::where('email', $request->email)->first();
        if($user) {
            $user->password = Hash::make($request->nouveau_password);
            $user->save();
        }

        // 3. مسح الكود باش ميستعملوش مرة خرى
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Votre mot de passe a été réinitialisé avec succès.'
        ], 200);
    }

   // ==========================================
    // 8. Suppression du compte (Soft Delete)
    // ==========================================
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable.'
            ], 404);
        }

        // 1. تبديل الإيميل باش يقدر يستعملو مرة خرى مستقبلا بلا مايوقع مشكل فـ DB (Unique)
        // وكنزيدو time() باش نضمنو أن الإيميل مايتعاودش
        $user->email = 'deleted_' . time() . '_' . $user->email;
        $user->status = 0; // اختياري: إلى كنتي كتستعمل status في بلايص خرين
        $user->save();

        // 2. كنسدو ليه ڭاع الجلسات ديالو باش يخرج من التطبيق بصفة نهائية
        $user->tokens()->delete(); 

        // 3. مسح الحساب (غادي يتخبى من التطبيق ولكن غيبقى فـ DB للـ Admin)
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Votre compte a été supprimé avec succès.'
        ], 200);
    }

    public function getProfileImage($filename)
{
    $path = storage_path('app/public/profils/' . $filename);

    if (!file_exists($path)) {
        return response()->json(['message' => 'Image introuvable.'], 404);
    }

    // إرسال الملف مباشرة كرد (Response)
    return response()->file($path);
}

}