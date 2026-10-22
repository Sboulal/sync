<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SyndicPaiementController extends Controller
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
    // 1. ÉCRAN 11: Liste des paiements
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

            if (!$exercice) return response()->json(['success' => true, 'data' => []]);

            $userNameCol = $this->col('users', 'full_name', 'name');

            $paiements = DB::table('encaissements')
                ->join('users', DB::raw('users.id::text'), '=', DB::raw('encaissements.owner_id::text'))
                ->join('user_owner_unit', DB::raw('user_owner_unit.user_id::text'), '=', DB::raw('users.id::text'))
                ->join('units', DB::raw('units.id::text'), '=', DB::raw('user_owner_unit.unit_id::text'))
                ->where('encaissements.se_identifier', $exercice->se_identifier)
                ->select(
                    'encaissements.id',
                    'encaissements.date',
                    'encaissements.amount',
                    'encaissements.title', // فيه الـ Reference لي غنحتاجو
                    'encaissements.sub_type_charges as payment_method', // استعملنا هادي باش نخزنو طريقة الدفع
                    "users.{$userNameCol} as owner_name",
                    'units.numero_porte as lot_id'
                )
                ->orderBy('encaissements.date', 'desc')
                ->get();

            $uniquePaiements = $paiements->unique('id')->values();

            $formatted = $uniquePaiements->map(function ($p) {
                $modeStr = strtolower($p->payment_method ?? 'virement');
                $modeColor = '#1A5EAC'; // Bleu
                if (str_contains($modeStr, 'espèc') || str_contains($modeStr, 'espece')) $modeColor = '#FF9800'; // Orange
                if (str_contains($modeStr, 'chèq') || str_contains($modeStr, 'cheque')) $modeColor = '#D32F2F'; // Rouge

                return [
                    'db_id' => $p->id,
                    'date' => \Carbon\Carbon::parse($p->date)->format('d/m/Y'),
                    'owner' => $p->owner_name,
                    'lot' => $p->lot_id,
                    'amount' => number_format($p->amount, 2, ',', ' ') . ' MAD',
                    'mode' => ucfirst($modeStr),
                    'reference' => $p->title ?? 'Non spécifiée',
                    'modeColorHex' => $modeColor
                ];
            });

            return response()->json(['success' => true, 'data' => $formatted], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 2. ÉCRAN 12: Enregistrer un paiement
    // ==========================================
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'payment_method' => 'required|string',
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

            if (!$exercice) throw new \Exception("Aucun exercice en cours pour enregistrer ce paiement.");

            // 🟢 إعداد الـ Title باش يشد المرجع (Reference)
            $title = "Paiement " . ucfirst($request->payment_method);
            if (!empty($request->reference)) {
                $title .= " - Réf: " . $request->reference;
            }

            // 🟢 توليد المعرف الإجباري
            $sen_id = 'SEN-' . time() . '-' . Str::random(5);

            // 1. تسجيل الأداء بالمواصفات الصارمة ديال Migration ديالك
            $insertData = [
                'sen_identifier' => $sen_id,
                'se_identifier' => $exercice->se_identifier,
                'owner_id' => $request->user_id,
                'title' => $title,
                'amount' => $request->amount,
                'date' => $request->date,
                'type_charges' => 'previsionnel', // إجباري فالداتابيز
                'sub_type_charges' => strtolower($request->payment_method), // كنحطو فيها طريقة الدفع
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('encaissements')->insert($insertData);

            // 2. تحديث الرصيد
            $propOwnerCol = $this->col('user_as_owner', 'propriete_id', 'sp_identifier');
            $balanceCol = Schema::hasColumn('user_as_owner', 'balance_prev') ? 'balance_prev' : 'solde';

            $ownerRecord = DB::table('user_as_owner')
                ->whereRaw("user_id::text = ?", [$request->user_id])
                ->whereRaw("{$propOwnerCol}::text = ?", [$propId])
                ->first();

            if ($ownerRecord) {
                DB::table('user_as_owner')
                    ->whereRaw("user_id::text = ?", [$request->user_id])
                    ->whereRaw("{$propOwnerCol}::text = ?", [$propId])
                    ->increment($balanceCol, $request->amount);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => "Paiement de {$request->amount} MAD enregistré. Le solde a été recalculé."]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 3. Outil : Obtenir les copropriétaires
    // ==========================================
    public function getCoproprietaires(Request $request)
    {
        try {
            $propId = $this->getProprieteId($request->user());
            $propOwnerCol = $this->col('user_as_owner', 'propriete_id', 'sp_identifier');
            $userNameCol = $this->col('users', 'full_name', 'name');

            $owners = DB::table('user_as_owner')
                ->join('users', DB::raw('users.id::text'), '=', DB::raw('user_as_owner.user_id::text'))
                ->join('user_owner_unit', DB::raw('user_owner_unit.user_id::text'), '=', DB::raw('users.id::text'))
                ->join('units', DB::raw('units.id::text'), '=', DB::raw('user_owner_unit.unit_id::text'))
                ->whereRaw("user_as_owner.{$propOwnerCol}::text = ?", [$propId])
                ->select(
                    'users.id as user_id',
                    "users.{$userNameCol} as owner_name",
                    'units.numero_porte as lot_id'
                )
                ->get()
                ->unique('user_id')
                ->values();

            return response()->json(['success' => true, 'data' => $owners]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}