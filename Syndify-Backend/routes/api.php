<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LotController;
use App\Http\Controllers\CoproprietaireController;
use App\Http\Controllers\CleRepartitionController;
use App\Http\Controllers\ExerciceController;
use App\Http\Controllers\BudgetOperationController;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\AppelFondsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClotureController;
use App\Http\Controllers\ImpayeController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ParametreController;
use App\Http\Controllers\Mobile\CoproMobileController;
use App\Http\Controllers\WebStandardAuthController;
use App\Http\Controllers\Mobile\SyndicAuthMobileController;
use App\Http\Controllers\Mobile\SyndicDashboardController;
use App\Http\Controllers\Mobile\SyndicLotController;
use App\Http\Controllers\Mobile\SyndicChargeController;
use App\Http\Controllers\Mobile\SyndicPaiementController;
use App\Http\Controllers\Mobile\SyndicDepenseController;
use App\Http\Controllers\Mobile\SyndicDocumentController;
use App\Http\Controllers\Mobile\SyndicAnnonceController;
use App\Http\Controllers\Mobile\SyndicNotificationController;
use App\Http\Controllers\Mobile\SyndicAssembleeController;

use App\Http\Middleware\GatewayAuth; 

Route::post('/login', [WebStandardAuthController::class, 'login']);

// ==========================================
// 🔓 ROUTES PUBLIQUES (Bla Token)
// ==========================================
Route::post('/mobile/verifier-email', [AuthController::class, 'verifierEmailMobile']);
Route::post('/mobile/login-otp', [AuthController::class, 'requestMobileOtp']); 
Route::post('/mobile/login', [AuthController::class, 'login']); 
Route::post('/mobile/verify-otp', [AuthController::class, 'verifyMobileOtp']);
Route::post('/mobile/register-mobile', [AuthController::class, 'registerMobile']);
Route::post('/register', [AuthController::class, 'register']);

// 🟢 ROUTES D'INSCRIPTION MOBILE (PUBLIQUES)
Route::post('/mobile/syndic/login', [SyndicAuthMobileController::class, 'login']);
Route::post('/mobile/syndic/register', [SyndicAuthMobileController::class, 'registerSyndic']); // 👈 Inscription Syndic
Route::post('/mobile/copro/register', [SyndicAuthMobileController::class, 'register']);       // 👈 Inscription Saken

// ==========================================
// 🌐 ROUTES WEB (Protégées par l'API Gateway - Bla Sanctum)
// ==========================================
Route::middleware([GatewayAuth::class])->group(function () {

    Route::post('/lots/liste', [LotController::class, 'liste']);
    Route::post('/lots/details', [LotController::class, 'details']);
    Route::post('/lots/ajouter', [LotController::class, 'ajouter']);
    Route::post('/lots/modifier', [LotController::class, 'modifier']);
    Route::post('/lots/supprimer', [LotController::class, 'supprimer']);
    Route::post('/lots/proprietaire/activer', [LotController::class, 'activerProprietaire']);
    Route::post('/lots/proprietaire/desactiver', [LotController::class, 'desactiverProprietaire']);
    Route::post('/lots/proprietaire/supprimer', [LotController::class, 'supprimerProprietaire']);
    Route::post('/proprietes/{id}/modifier', [AuthController::class, 'modifierPropriete']);
    Route::delete('/proprietes/{id}/supprimer', [AuthController::class, 'supprimerProprietaire']); 

    Route::post('/coproprietaires/liste', [CoproprietaireController::class, 'liste']);
    Route::post('/coproprietaires/desactiver', [CoproprietaireController::class, 'desactiver']);
    Route::post('/coproprietaires/ajouter', [CoproprietaireController::class, 'ajouter']);
    Route::post('/coproprietaires/supprimer', [CoproprietaireController::class, 'supprimer']);
    Route::post('/coproprietaires/historique', [CoproprietaireController::class, 'historique']);

    Route::post('/proprietes/ajouter', [CoproprietaireController::class, 'ajouter']);

    Route::post('/cles-repartition/liste', [CleRepartitionController::class, 'liste']);
    Route::post('/cles-repartition/ajouter', [CleRepartitionController::class, 'ajouter']);
    Route::post('/cles-repartition/modifier', [CleRepartitionController::class, 'modifier']);
    Route::post('/cles-repartition/supprimer', [CleRepartitionController::class, 'supprimer']);

    Route::post('/exercices/liste', [ExerciceController::class, 'liste']);
    Route::post('/exercices/ajouter', [ExerciceController::class, 'ajouter']);
    Route::post('/exercices/modifier', [ExerciceController::class, 'modifier']);
    Route::post('/exercices/supprimer', [ExerciceController::class, 'supprimer']);
    Route::post('/exercices/demarrer', [ExerciceController::class, 'demarrer']);

    Route::post('/budgets/charger', [BudgetOperationController::class, 'chargerDonnees']);
    Route::post('/budgets/releve', [BudgetOperationController::class, 'telechargerReleve']);
    Route::post('/encaissements/ajouter', [BudgetOperationController::class, 'ajouterEncaissement']);
    Route::post('/encaissements/supprimer', [BudgetOperationController::class, 'supprimerEncaissement']);
    Route::post('/depenses/ajouter', [BudgetOperationController::class, 'ajouterDepense']);
    Route::post('/depenses/supprimer', [BudgetOperationController::class, 'supprimerDepense']);

    Route::post('/simulation/charger', [SimulationController::class, 'chargerDonneesSimulation']);

    Route::post('/appels-fonds/liste', [AppelFondsController::class, 'liste']);
    Route::post('/appels-fonds/ajouter-planifie', [AppelFondsController::class, 'ajouterPlanifie']);
    Route::post('/appels-fonds/ajouter-exceptionnel', [AppelFondsController::class, 'ajouterExceptionnel']);
    Route::post('/appels-fonds/generer', [AppelFondsController::class, 'generer']);
    Route::post('/appels-fonds/envoyer', [AppelFondsController::class, 'envoyer']);
    Route::post('/appels-fonds/details', [AppelFondsController::class, 'details']);
    Route::post('/appels-fonds/supprimer', [AppelFondsController::class, 'supprimer']);

    Route::post('/clotures/charger', [ClotureController::class, 'charger']);
    Route::post('/clotures/enregistrer', [ClotureController::class, 'enregistrer']);
    Route::post('/clotures/finaliser', [ClotureController::class, 'finaliser']);

    Route::post('/dashboard/data', [DashboardController::class, 'getDashboardData']);  

    Route::post('/impayes/liste', [ImpayeController::class, 'listeImpayes']);
    Route::post('/impayes/rappels', [ImpayeController::class, 'listeRappels']);
    Route::post('/impayes/envoyer-rappel', [ImpayeController::class, 'envoyerRappel']); 

    Route::get('/parametres', [ParametreController::class, 'getParametres']);
    Route::post('/parametres/update', [ParametreController::class, 'updateParametres']);

    Route::post('/documents/principal', [DocumentController::class, 'chargerDossierPrincipal']);
    Route::post('/documents/sous-dossier', [DocumentController::class, 'accederSousDossier']);
    Route::post('/documents/telecharger', [DocumentController::class, 'telecharger']);
    Route::post('/documents/supprimer', [DocumentController::class, 'supprimer']);
    Route::post('/documents/rechercher', [DocumentController::class, 'rechercher']);

    Route::post('/home-data', [AuthController::class, 'getHomeData']);
    Route::post('/logout', [AuthController::class, 'logout']);

});

Route::post('/forgot-password/send-code', [SyndicAuthMobileController::class, 'sendResetCode']);
Route::post('/forgot-password/reset', [SyndicAuthMobileController::class, 'resetPasswordWithCode']);
Route::get('/storage/profils/{filename}', [App\Http\Controllers\Mobile\SyndicAuthMobileController::class, 'getProfileImage']);
// 🟢 Route globale pour la suppression du compte, accessible par copropriétaires et syndics
Route::middleware('auth:sanctum')->delete('/mobile/account/delete', [SyndicAuthMobileController::class, 'deleteAccount']);

// ==========================================
// 📱 ROUTES SÉCURISÉES MOBILE COPRO (Khasshom Token Sanctum)
// ==========================================
Route::prefix('mobile/copro')->middleware('auth:sanctum')->group(function () {
    
    Route::get('/profil', [SyndicAuthMobileController::class, 'profil']);
    Route::put('/profil/password', [SyndicAuthMobileController::class, 'updatePassword']);
    Route::post('/profil/photo', [SyndicAuthMobileController::class, 'updatePhoto']); 
    Route::post('/logout', [SyndicAuthMobileController::class, 'logout']);
    Route::get('/dashboard', [CoproMobileController::class, 'getDashboard']);
    Route::get('/ag', [CoproMobileController::class, 'getMesAssemblees']);

    Route::get('/notifications', [CoproMobileController::class, 'getNotifications']);
    Route::post('/notifications/{id}/lu', [CoproMobileController::class, 'marquerNotificationLue']); 
    Route::post('/update-fcm-token', [SyndicAuthMobileController::class, 'updateFcmToken']);
    
    Route::get('/mes-charges', [CoproMobileController::class, 'getMesCharges']);
    Route::get('/charges/{id}/pdf', [SyndicChargeController::class, 'downloadPdf']);
    Route::get('/mes-paiements', [CoproMobileController::class, 'getMesPaiements']);

    Route::get('/mes-documents', [CoproMobileController::class, 'getMesDocuments']);
    Route::post('/procuration', [CoproMobileController::class, 'envoyerProcuration']);
    Route::get('/mes-annonces', [CoproMobileController::class, 'getMesAnnonces']);

    Route::post('/valider-code', [CoproMobileController::class, 'validerCodeResidence']);
    Route::get('/historique', [CoproMobileController::class, 'getHistorique']);
    Route::get('/mes-sirets', [CoproMobileController::class, 'getMesSirets']);
    Route::get('/mes-biens', [CoproMobileController::class, 'getBiens']);
    Route::get('/exercices', [CoproMobileController::class, 'getExercices']);
    Route::get('/exercices/{id}', [CoproMobileController::class, 'getExerciceDetails']);
});

// ==========================================
// 📱 ROUTES SÉCURISÉES SYNDIC MOBILE (Sanctum)
// ==========================================
Route::prefix('mobile/syndic')->middleware('auth:sanctum')->group(function () {
    
    Route::get('/dashboard', [SyndicDashboardController::class, 'index']);

    Route::get('/notifications', [SyndicNotificationController::class, 'index']); 
    Route::post('/notifications/{id}/lu', [SyndicNotificationController::class, 'markAsRead']); 
    Route::post('/update-fcm-token', [SyndicAuthMobileController::class, 'updateFcmToken']);
    
    Route::get('/lots', [SyndicLotController::class, 'index']);
    Route::post('/lots', [SyndicLotController::class, 'store']); 
    Route::get('/lots/{id}', [SyndicLotController::class, 'show']); 
    Route::put('/lots/{id}', [SyndicLotController::class, 'update']);

    Route::get('/charges', [SyndicChargeController::class, 'index']);
    Route::post('/charges', [SyndicChargeController::class, 'store']); 
    Route::get('/charges/{id}', [SyndicChargeController::class, 'show']);

    Route::get('/paiements', [SyndicPaiementController::class, 'index']);
    Route::post('/paiements', [SyndicPaiementController::class, 'store']);
    Route::get('/paiements/coproprietaires', [SyndicPaiementController::class, 'getCoproprietaires']); 
    
    Route::get('/depenses', [SyndicDepenseController::class, 'index']);
    Route::post('/depenses', [SyndicDepenseController::class, 'store']);
    
    Route::get('/documents', [SyndicDocumentController::class, 'index']);
    Route::post('/documents', [SyndicDocumentController::class, 'store']);

    Route::get('/annonces', [SyndicAnnonceController::class, 'index']);
    Route::post('/annonces', [SyndicAnnonceController::class, 'store']);
    Route::get('/assemblees', [SyndicAssembleeController::class, 'index']);
    Route::post('/assemblees', [SyndicAssembleeController::class, 'store']);

    // 🟢 Routes d'attente w validation l-Syndic
    Route::get('/copro-en-attente', [SyndicAuthMobileController::class, 'getCoproprietairesEnAttente']);
    Route::post('/valider-copro/{id}', [SyndicAuthMobileController::class, 'validerCoproprietaire']);

    Route::get('/profil', [SyndicAuthMobileController::class, 'profil']);
    Route::put('/profil/password', [SyndicAuthMobileController::class, 'updatePassword']);
    Route::post('/profil/photo', [SyndicAuthMobileController::class, 'updatePhoto']); 
    Route::post('/logout', [SyndicAuthMobileController::class, 'logout']);
});