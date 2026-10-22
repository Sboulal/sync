<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getDashboardData(Request $request)
    {
        Carbon::setLocale('fr');
        
        try {
            $user = $request->attributes->get('gateway_user');
            $payload = $request->attributes->get('gateway_payload');
            
            if (!$user) {
                return [
                    'data' => ['success' => false, 'message' => 'Non authentifié par le Gateway.'],
                    'status' => 401
                ];
            }

            $user_id = $user->id;

            $userPk       = Schema::hasColumn('users', 'identifier') ? 'identifier' : 'id';
            $userNameCol  = Schema::hasColumn('users', 'full_name')  ? 'full_name'  : 'name';
            $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
            $propIdCol    = Schema::hasColumn('proprietes', 'sp_identifier')   ? 'sp_identifier' : 'id';
            $exPropCol    = Schema::hasColumn('exercices', 'propriete_id')     ? 'propriete_id'  : 'sp_identifier';

            // 🟢 الحل الذهبي: كنجبدو الـ ID من الـ payload ديال ياسين
            $requested_sp_id = $payload['data']['residence_id'] ?? $request->header('residence_id') ?? $request->input('residence_id');
            $se_identifier = $payload['data']['se_identifier'] ?? $request->input('se_identifier');

            // 2. كنقلبو على ديك الإقامة بالضبط
            $query = DB::table('user_as_owner')->where('user_id', $user_id);

            if ($requested_sp_id) {
                $query->where($propOwnerCol, $requested_sp_id);
            }

            $link = $query->first();

            // 3. إيلا مالقاش الإقامة اللي تطلبَات، كيجيب الأولى كاحتياط
            if (!$link) {
                $link = DB::table('user_as_owner')->where('user_id', $user_id)->first();
            }

            if (!$link) {
                return [
                    'data' => ['success' => false, 'message' => 'Utilisateur non lié à une résidence.'],
                    'status' => 404
                ];
            }

            $sp_id = $link->$propOwnerCol;
            $role  = strtolower($link->role ?? 'syndic');

            // 1. Propriété
            $propriete = DB::table('proprietes')->where($propIdCol, $sp_id)->first();

            // 2. Exercice
            if ($se_identifier) {
                $exercice = DB::table('exercices')
                    ->where('se_identifier', $se_identifier)
                    ->where($exPropCol, $sp_id)
                    ->first();
            } else {
                $exercice = DB::table('exercices')
                    ->where($exPropCol, $sp_id)
                    ->whereIn('status', ['en cours', 'en attente'])
                    ->first();

                if (!$exercice) {
                    $exercice = DB::table('exercices')
                        ->where($exPropCol, $sp_id)
                        ->whereIn('status', ['clos', 'Clos'])
                        ->orderBy('end_date', 'desc')
                        ->first();
                }
            }

            // 3. Tous les exercices
            $tousLesExercices = DB::table('exercices')->where($exPropCol, $sp_id)->get();

            // Aucun exercice
            if (!$exercice) {
                return [
                    'data' => [
                        'success' => true,
                        'residence' => [
                            'nom'      => $propriete->nom     ?? 'Résidence Inconnue',
                            'adresse'  => $propriete->address ?? 'Adresse non définie',
                            'exercice' => '—',
                            'periode'  => '—',
                            'gerant'   => 'Cabinet Syndic',
                            'role'     => ucfirst($role)
                        ],
                        'exercices'            => $tousLesExercices,
                        'exercice_selectionne' => null,
                        'budget' => [
                            'totalAnnee'       => '0',
                            'previsionnel'     => '0',
                            'travaux'          => '0',
                            'depenses'         => '0',
                            'depenses_prev'    => '0',
                            'depenses_trav'    => '0',
                            'pourcentage'      => 0,
                            'pourcentage_prev' => 0,
                            'pourcentage_trav' => 0,
                        ],
                        'trimestres'       => ['previsionnel' => [], 'travaux' => []],
                        'soldes'           => [],
                        'totalDu'          => '0',
                        'evenements'       => [],
                        'flux_tresorerie'  => ['labels' => [], 'encaissements' => [], 'depenses' => []]
                    ],
                    'status' => 200
                ];
            }

            // 4. Budgets
            $budgetPrev    = DB::table('charges_previsionnelles')->where('se_identifier', $exercice->se_identifier)->first();
            $budgetTravaux = DB::table('charges_travaux')->where('se_identifier', $exercice->se_identifier)->first();

            $valeurBudgetPrev    = $budgetPrev    ? (float)$budgetPrev->budget    : 0;
            $valeurBudgetTravaux = $budgetTravaux ? (float)$budgetTravaux->budget : 0;
            $totalBudget         = $valeurBudgetPrev + $valeurBudgetTravaux;

            $depensesPrev = DB::table('depenses')
                ->where('se_identifier', $exercice->se_identifier)
                ->where('type_charges', 'previsionnel')
                ->sum('amount');

            $depensesTrav = DB::table('depenses')
                ->where('se_identifier', $exercice->se_identifier)
                ->where('type_charges', 'travaux')
                ->sum('amount');

            $totalDepenses = $depensesPrev + $depensesTrav;

            $pourcentage     = $totalBudget        > 0 ? min(100, round(($totalDepenses / $totalBudget)         * 100)) : 0;
            $pourcentagePrev = $valeurBudgetPrev    > 0 ? min(100, round(($depensesPrev  / $valeurBudgetPrev)    * 100)) : 0;
            $pourcentageTrav = $valeurBudgetTravaux > 0 ? min(100, round(($depensesTrav  / $valeurBudgetTravaux) * 100)) : 0;

            // 5. Périodes
            $typePeriode = strtolower(trim($exercice->period ?? 'trimestriel'));

            switch ($typePeriode) {
                case 'mensuel': case 'monthly': case 'mois':
                    $moisParPeriode = 1;  $nombrePeriodes = 12; $nomPeriode = 'Mois';          break;
                case 'quadrimestriel': case 'quadrimestre': case 'quadrimonthly':
                    $moisParPeriode = 4;  $nombrePeriodes = 3;  $nomPeriode = 'Quadrimestre';  break;
                case 'semestriel': case 'semestre': case 'biannual':
                    $moisParPeriode = 6;  $nombrePeriodes = 2;  $nomPeriode = 'Semestre';      break;
                case 'annuel': case 'annual': case 'yearly':
                    $moisParPeriode = 12; $nombrePeriodes = 1;  $nomPeriode = 'Année';         break;
                default:
                    $moisParPeriode = 3;  $nombrePeriodes = 4;  $nomPeriode = 'Trimestre';     break;
            }

            $triPrev = [];
            $triTrav = [];

            $budgetParPeriodePrev = $nombrePeriodes > 0 ? ($valeurBudgetPrev    / $nombrePeriodes) : 0;
            $budgetParPeriodeTrav = $nombrePeriodes > 0 ? ($valeurBudgetTravaux / $nombrePeriodes) : 0;

            if ($exercice->start_date) {
                $startDate = Carbon::parse($exercice->start_date);

                for ($i = 0; $i < $nombrePeriodes; $i++) {
                    $debutPeriode = $startDate->copy()->addMonths($i * $moisParPeriode);
                    $finPeriode   = $debutPeriode->copy()->addMonths($moisParPeriode)->subDay();

                    $nomAffichage = $moisParPeriode === 1
                        ? ucfirst($debutPeriode->translatedFormat('F'))
                        : $nomPeriode . ' ' . ($i + 1);

                    $depPrev = DB::table('depenses')
                        ->where('se_identifier', $exercice->se_identifier)
                        ->where('type_charges', 'previsionnel')
                        ->whereBetween('created_at', [$debutPeriode, $finPeriode])
                        ->sum('amount');

                    $pctPrev = $budgetParPeriodePrev > 0 ? min(100, round(($depPrev / $budgetParPeriodePrev) * 100)) : 0;

                    $triPrev[] = [
                        'nom'         => $nomAffichage,
                        'total'       => number_format($budgetParPeriodePrev, 0, '.', ''),
                        'depense'     => number_format($depPrev, 0, '.', ''),
                        'pourcentage' => $pctPrev
                    ];

                    $depTrav = DB::table('depenses')
                        ->where('se_identifier', $exercice->se_identifier)
                        ->where('type_charges', 'travaux')
                        ->whereBetween('created_at', [$debutPeriode, $finPeriode])
                        ->sum('amount');

                    $pctTrav = $budgetParPeriodeTrav > 0 ? min(100, round(($depTrav / $budgetParPeriodeTrav) * 100)) : 0;

                    $triTrav[] = [
                        'nom'         => $nomAffichage,
                        'total'       => number_format($budgetParPeriodeTrav, 0, '.', ''),
                        'depense'     => number_format($depTrav, 0, '.', ''),
                        'pourcentage' => $pctTrav
                    ];
                }
            }

            // 6. Flux de trésorerie
            $fluxLabels        = [];
            $fluxEncaissements = [];
            $fluxDepenses      = [];

            if ($exercice->start_date) {
                $startDate = Carbon::parse($exercice->start_date);
                for ($i = 0; $i < 12; $i++) {
                    $mois = $startDate->copy()->addMonths($i);
                    $fluxLabels[] = $mois->translatedFormat('M y');

                    $fluxEncaissements[] = (float) DB::table('encaissements')
                        ->where('se_identifier', $exercice->se_identifier)
                        ->whereYear('created_at',  $mois->year)
                        ->whereMonth('created_at', $mois->month)
                        ->sum('amount');

                    $fluxDepenses[] = (float) DB::table('depenses')
                        ->where('se_identifier', $exercice->se_identifier)
                        ->whereYear('created_at',  $mois->year)
                        ->whereMonth('created_at', $mois->month)
                        ->sum('amount');
                }
            }

            // Événements
            $evenements = [];
            if (Schema::hasTable('assemblees')) {
                $evenements = DB::table('assemblees')
                    ->where('propriete_id', $sp_id)
                    ->where('date_assemblee', '>=', Carbon::now()->subDays(5))
                    ->get();
            }

            // 7. Soldes
            $soldes  = [];
            $totalDu = 0;

            if ($role === 'syndic') {
                $hasBalancePrev = Schema::hasColumn('user_as_owner', 'balance_prev');
                $hasBalanceTrav = Schema::hasColumn('user_as_owner', 'balance_trav');
                $hasSolde       = Schema::hasColumn('user_as_owner', 'solde');

                $selectCols = [
                    'users.id as user_id',
                    'users.identifier as su_identifier',
                    'users.' . $userNameCol . ' as nom',
                    'users.email',
                ];

                if ($hasBalancePrev) $selectCols[] = 'user_as_owner.balance_prev';
                if ($hasBalanceTrav) $selectCols[] = 'user_as_owner.balance_trav';
                if ($hasSolde)       $selectCols[] = 'user_as_owner.solde';

                $query = DB::table('user_as_owner')
                    ->join('users', 'user_as_owner.user_id', '=', 'users.id')
                    ->whereRaw('CAST(user_as_owner.' . $propOwnerCol . ' AS VARCHAR) = ?', [(string)$sp_id])
                    ->where('user_as_owner.user_id', '!=', $user_id);

                if (Schema::hasColumn('user_as_owner', 'role')) {
                    $query->where(function ($q) {
                        $q->where('user_as_owner.role', '!=', 'syndic')
                          ->orWhereNull('user_as_owner.role');
                    });
                }

                $soldesDb = $query->select($selectCols)->distinct()->get();

                foreach ($soldesDb as $s) {
                    $soldeTotal = 0;
                    if ($hasBalancePrev) $soldeTotal += (float)($s->balance_prev ?? 0);
                    if ($hasBalanceTrav) $soldeTotal += (float)($s->balance_trav ?? 0);
                    if ($hasSolde)       $soldeTotal += (float)($s->solde ?? 0);

                    $visualId   = abs(crc32($s->user_id)) % 100000;
                    $finalId    = 'COP-' . str_pad($visualId, 8, '0', STR_PAD_LEFT);

                    $soldes[] = [
                        'id'            => $finalId,
                        'user_id'       => $s->user_id,
                        'su_identifier' => !empty($s->su_identifier) ? $s->su_identifier : (string)$s->user_id,
                        'nom'           => $s->nom ?: $s->email,
                        'solde'         => number_format((float)$soldeTotal, 2, '.', ''),
                        'isNegatif'     => $soldeTotal < 0,
                        'action'        => $soldeTotal < 0 ? 'Relancer' : ''
                    ];

                    if ($soldeTotal < 0) $totalDu += abs($soldeTotal);
                }
            }
            
            // 8. Response finale
            return [
                'data' => [
                    'success' => true,
                    'residence' => [
                        'nom'      => $propriete->nom     ?? 'Résidence Inconnue',
                        'adresse'  => $propriete->address ?? 'Adresse non définie',
                        'exercice' => date('Y', strtotime($exercice->start_date)),
                        'periode'  => $exercice->period   ?? 'Trimestre',
                        'gerant'   => 'Cabinet Syndic',
                        'role'     => ucfirst($role)
                    ],
                    'exercices'            => $tousLesExercices,
                    'exercice_selectionne' => $exercice->se_identifier,
                    'budget' => [
                        'totalAnnee'       => number_format($totalBudget,         0, '.', ''),
                        'previsionnel'     => number_format($valeurBudgetPrev,    0, '.', ''),
                        'travaux'          => number_format($valeurBudgetTravaux, 0, '.', ''),
                        'depenses'         => number_format($totalDepenses,       0, '.', ''),
                        'depenses_prev'    => number_format($depensesPrev,        0, '.', ''),
                        'depenses_trav'    => number_format($depensesTrav,        0, '.', ''),
                        'pourcentage'      => $pourcentage,
                        'pourcentage_prev' => $pourcentagePrev,
                        'pourcentage_trav' => $pourcentageTrav,
                    ],
                    'trimestres' => [
                        'previsionnel' => $triPrev ?? [],
                        'travaux'      => $triTrav ?? [],
                    ],
                    'soldes'          => $soldes ?? [],
                    'totalDu'         => number_format($totalDu, 0, '.', ''),
                    'evenements'      => $evenements ?? [],
                    'flux_tresorerie' => [
                        'labels'        => $fluxLabels ?? [],
                        'encaissements' => $fluxEncaissements ?? [],
                        'depenses'      => $fluxDepenses ?? []
                    ]
                ],
                'status' => 200
            ];

        } catch (\Exception $e) {
            Log::error("Erreur Dashboard: " . $e->getMessage());
            return [
                'data' => [
                    'success' => false,
                    'message' => 'Erreur server: ' . $e->getMessage()
                ],
                'status' => 500
            ];
        }
    }
}