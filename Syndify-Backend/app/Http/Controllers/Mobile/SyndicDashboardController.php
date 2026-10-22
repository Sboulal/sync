<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class SyndicDashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // 1. Déterminer la propriété du Syndic
            $propCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
            $propId = DB::table('user_as_owner')
                ->where('user_id', $user->id)
                ->where('role', 'syndic')
                ->value($propCol);

            if (!$propId) {
                return response()->json(['success' => false, 'message' => 'Aucune résidence trouvée.']);
            }

            $nomRes = DB::table('proprietes')->where('id', $propId)->value('nom') ?? 'Résidence';

            // 2. Exercice en cours
            $exCol = Schema::hasColumn('exercices', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
            $exercice = DB::table('exercices')
                ->whereRaw("{$exCol}::text = ?", [$propId])
                ->where('status', 'en cours')
                ->first();
            
            $se_id = $exercice ? $exercice->se_identifier : null;

            // 3. Calcul des KPIs (LE SECRET EST ICI 🟢)
            $nbLots = DB::table('units')->where('propriete_id', $propId)->count();
            $solde = DB::table('user_as_owner')->whereRaw("{$propCol}::text = ?", [$propId])->sum('balance_prev');

            $charges = 0; 
            $encaisse = 0; 
            $depenses = 0;

            if ($se_id) {
                $charges = DB::table('appels_fonds')->where('se_identifier', $se_id)->sum('amount');
                $encaisse = DB::table('encaissements')->where('se_identifier', $se_id)->sum('amount');
                // 🟢 LA LIGNE QUI MANQUAIT POUR LES DÉPENSES
                $depenses = DB::table('depenses')->where('se_identifier', $se_id)->sum('amount'); 
            }

            // Calcul simple des impayés
            $impayes = max(0, $charges - $encaisse);

            // 4. Dernières activités (Paiements et Dépenses récents)
            $activites = [];
            if ($se_id) {
                $paiements = DB::table('encaissements')->where('se_identifier', $se_id)->orderBy('date', 'desc')->limit(3)->get();
                foreach($paiements as $p) {
                    $activites[] = [
                        'type' => 'paiement',
                        'titre' => $p->title,
                        'date' => Carbon::parse($p->date)->locale('fr')->diffForHumans(),
                        'montant' => '+ ' . number_format($p->amount, 2, ',', ' ') . ' MAD',
                        'couleur' => '#4CAF50' // Vert
                    ];
                }
            }

            // 5. Envoi vers Flutter
            return response()->json([
                'success' => true,
                'data' => [
                    'utilisateur' => [
                        'prenom' => explode(' ', trim($user->full_name ?? $user->name ?? 'Syndic'))[0],
                        'photo_url' => $user->photo_url ?? ''
                    ],
                    'copropriete' => ['nom' => $nomRes],
                    'kpis' => [
                        'solde' => $solde,
                        'lots' => $nbLots,
                        'charges_appelees' => $charges,
                        'encaisse' => $encaisse,
                        'impayes' => $impayes,
                        'depenses' => $depenses // 🟢 FLUTTER VA ENFIN RECEVOIR CE CHIFFRE !
                    ],
                    'dernieres_activites' => $activites
                ]
            ], 200);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}