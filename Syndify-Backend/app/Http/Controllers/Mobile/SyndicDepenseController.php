<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class SyndicDepenseController extends Controller
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

    // ==========================================
    // 1. ÉCRAN 13: Liste des dépenses
    // ==========================================
    public function index(Request $request)
    {
        try {
            $propId = $this->getProprieteId($request->user());
            if (!$propId) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

            $exCol = $this->col('exercices', 'propriete_id', 'sp_identifier');
            $exercice = DB::table('exercices')
                ->whereRaw("{$exCol}::text = ?", [$propId])
                ->where('status', 'en cours')
                ->first();

            if (!$exercice) {
                $exercice = DB::table('exercices')
                    ->whereRaw("{$exCol}::text = ?", [$propId])
                    ->orderBy('start_date', 'desc')
                    ->first();
            }

            if (!$exercice) return response()->json(['success' => true, 'total_mois' => 0, 'data' => []]);

            // جلب المصاريف
            $depenses = DB::table('depenses')
                ->where('se_identifier', $exercice->se_identifier)
                ->orderBy('date', 'desc')
                ->get();

            // حساب مجموع مصاريف الشهر الحالي (Total du mois)
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;
            
            $totalMois = $depenses->filter(function($d) use ($currentMonth, $currentYear) {
                $date = Carbon::parse($d->date);
                return $date->month === $currentMonth && $date->year === $currentYear;
            })->sum('amount');

            $formatted = $depenses->map(function ($d) {
                $cat = strtolower($d->sub_type_charges ?? 'autre');
                $color = '#1A5EAC'; // Bleu par défaut
                $icon = 'build'; // Icon par défaut

                if (str_contains($cat, 'entretien') || str_contains($cat, 'nettoyage')) { $color = '#2196F3'; $icon = 'cleaning_services'; }
                elseif (str_contains($cat, 'maintenance') || str_contains($cat, 'réparation')) { $color = '#FF9800'; $icon = 'build'; }
                elseif (str_contains($cat, 'admin') || str_contains($cat, 'frais')) { $color = '#9C27B0'; $icon = 'description'; }

                return [
                    'id' => $d->id,
                    'date' => Carbon::parse($d->date)->format('d/m/Y'),
                    'title' => $d->title,
                    'category' => ucfirst($d->sub_type_charges ?? 'Général'),
                    'amount' => number_format($d->amount, 2, ',', ' ') . ' MAD',
                    'colorHex' => $color,
                    'iconString' => $icon
                ];
            });

            return response()->json([
                'success' => true, 
                'total_mois' => number_format($totalMois, 2, ',', ' '), 
                'data' => $formatted
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 2. ÉCRAN 14: Ajouter une dépense (AVEC FICHIER)
    // ==========================================
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'category' => 'required|string',
            'document' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120', // 🟢 Validation du fichier (Max 5MB)
        ]);

        DB::beginTransaction();
        try {
            $propId = $this->getProprieteId($request->user());
            if (!$propId) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

            $exCol = $this->col('exercices', 'propriete_id', 'sp_identifier');
            $exercice = DB::table('exercices')
                ->whereRaw("{$exCol}::text = ?", [$propId])
                ->where('status', 'en cours')
                ->first();

            if (!$exercice) throw new \Exception("Aucun exercice en cours.");

            $cleCol = $this->col('cle_repartitions', 'propriete_id', 'sp_identifier');
            $cle = DB::table('cle_repartitions')->whereRaw("{$cleCol}::text = ?", [$propId])->first();
            if (!$cle) throw new \Exception("Veuillez d'abord créer une clé de répartition.");

            // 🟢 Sauvegarde du fichier justificatif s'il existe
            $documentUrl = null;
            if ($request->hasFile('document')) {
                $path = $request->file('document')->store("proprietes/{$propId}/factures", 'public');
                
                // On donne les droits pour éviter l'erreur 403 de Nginx
                $absolutePath = storage_path('app/public/' . $path);
                if (file_exists($absolutePath)) chmod($absolutePath, 0777);
                
                $documentUrl = '/storage/' . $path;
            }

            $sdep_id = 'DEP-' . time() . rand(10, 99);
            $title = $request->title;
            if (!empty($request->fournisseur)) $title .= ' (' . $request->fournisseur . ')';
            if (!empty($request->reference)) $title .= ' - Réf: ' . $request->reference;

            $insertData = [
                'sdep_identifier' => $sdep_id,
                'se_identifier' => $exercice->se_identifier,
                'cle_repartition_id' => $cle->id,
                'title' => $title,
                'amount' => $request->amount,
                'date' => $request->date,
                'type_charges' => 'previsionnel',
                'sub_type_charges' => strtolower($request->category),
            ];

            // Si la colonne document_url existe, on l'ajoute
            if (Schema::hasColumn('depenses', 'document_url') && $documentUrl) {
                $insertData['document_url'] = $documentUrl;
            }

            if (Schema::hasColumn('depenses', 'created_at')) $insertData['created_at'] = now();
            if (Schema::hasColumn('depenses', 'updated_at')) $insertData['updated_at'] = now();

            $depenseId = DB::table('depenses')->insertGetId($insertData);

            $cleIdCol = $this->col('unit_to_key', 'cle_repartition_id', 'key_id');
            $total_tantiemes = $cle->tantiemes_total ?? DB::table('unit_to_key')->where($cleIdCol, $cle->id)->sum('tantieme');
            if ($total_tantiemes <= 0) $total_tantiemes = 10000;

            $lots = DB::table('unit_to_key')
                ->join('user_owner_unit', 'unit_to_key.unit_id', '=', 'user_owner_unit.unit_id')
                ->where("unit_to_key.{$cleIdCol}", $cle->id)
                ->where('user_owner_unit.status', 1)
                ->select('user_owner_unit.user_id as final_user_id', 'unit_to_key.tantieme')
                ->get();

            $ownerAmounts = [];
            foreach ($lots as $lot) {
                $part = $request->amount * (($lot->tantieme ?? 0) / $total_tantiemes);
                $u_id = $lot->final_user_id;
                $ownerAmounts[$u_id] = ($ownerAmounts[$u_id] ?? 0) + $part;
            }

            $propOwnerCol = $this->col('user_as_owner', 'propriete_id', 'sp_identifier');
            $balanceCol = Schema::hasColumn('user_as_owner', 'balance_prev') ? 'balance_prev' : 'solde';

            foreach ($ownerAmounts as $su_id => $montant_calcule) {
                DB::table('depense_for_owner')->insert([
                    'depense_id' => $depenseId,
                    'user_id' => $su_id,
                    'amount_due' => $montant_calcule,
                    'created_at' => now(),
                ]);

                DB::table('user_as_owner')
                    ->whereRaw("user_id::text = ?", [$su_id])
                    ->whereRaw("{$propOwnerCol}::text = ?", [$propId])
                    ->decrement($balanceCol, $montant_calcule);
            }

            DB::table('charges_previsionnelles')
                ->where('se_identifier', $exercice->se_identifier)
                ->increment('total_depenses', $request->amount);

            DB::commit();
            return response()->json(['success' => true, 'message' => "Dépense de {$request->amount} MAD enregistrée avec succès."]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
}