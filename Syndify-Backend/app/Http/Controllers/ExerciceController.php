<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExerciceController extends Controller
{
    // ========================================================
    // 🟢 FONCTION SÉCURISÉE AVEC AUTHENTIFICATION RÉELLE
    // ========================================================
    private function getProprieteId(Request $request)
    {
        $userId = auth()->id(); 
        if (!$userId) return null; 

        $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        // 🟢 كنقراو residence_id من (Headers -> Body -> Cookie)
        $residenceId = $request->header('residence_id') ?? $request->input('residence_id') ?? $request->cookie('residence_id');

        if ($residenceId) {
            $link = DB::table('user_as_owner')
                ->where('user_id', (string)$userId)
                ->where($propOwnerCol, $residenceId)
                ->first();
            if ($link) return $residenceId;
        }

        $link = DB::table('user_as_owner')->where('user_id', (string)$userId)->first();
        return $link ? $link->$propOwnerCol : null;
    }

    // ==========================================
    // 1. Liste des Exercices
    // ==========================================
    public function liste(Request $request)
    {
        $sp_id = $this->getProprieteId($request); // 🟢 FIX
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $propCol = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
        $residence = DB::table('proprietes')->where($propCol, $sp_id)->first();

        // 🟢 FIX: كنجيبو غير الـ Exercices ديال هاد الإقامة بالضبط
        $exPropCol = Schema::hasColumn('exercices', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        
        $exercices = DB::table('exercices')
            ->where($exPropCol, $sp_id)
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json([
            'success'   => true,
            'residence' => [
                'nom'     => $residence->nom     ?? 'Résidence',
                'adresse' => $residence->address ?? 'Adresse non définie'
            ],
            'data'      => $exercices
        ]);
    }

    // ==========================================
    // 2. Ajouter un Exercice
    // ==========================================
    public function ajouter(Request $request)
    {
        $sp_id = $this->getProprieteId($request); // 🟢 FIX
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
            'period'     => 'required|string',
            'budget'     => 'required|numeric'
        ]);

        $exPropCol = Schema::hasColumn('exercices', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        // التأكد واش كاين شي Exercice "En cours"
        $enCoursExists = DB::table('exercices')
            ->where($exPropCol, $sp_id)
            ->where('status', 'en cours')
            ->exists();

        if ($enCoursExists) {
            return response()->json(['success' => false, 'message' => "Un exercice est déjà 'En cours'. Veuillez le clôturer avant d'en créer un autre."], 400);
        }

        DB::beginTransaction();
        try {
            $se_identifier = 'EX-' . date('Y') . '-' . rand(1000, 9999);

            DB::table('exercices')->insert([
                'se_identifier' => $se_identifier,
                $exPropCol      => $sp_id,
                'start_date'    => $request->start_date,
                'end_date'      => $request->end_date,
                'period'        => $request->period,
                'status'        => 'en cours',
                'created_at'    => now(),
                'updated_at'    => now()
            ]);

            DB::table('charges_previsionnelles')->insert([
                'sc_identifier' => 'CP-' . rand(1000, 9999),
                'se_identifier' => $se_identifier,
                'budget'        => $request->budget,
                'created_at'    => now(),
                'updated_at'    => now()
            ]);

            DB::table('charges_travaux')->insert([
                'sct_identifier' => 'CT-' . rand(1000, 9999),
                'se_identifier'  => $se_identifier,
                'budget'         => $request->budget_travaux ?? 0,
                'created_at'     => now(),
                'updated_at'     => now()
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Exercice créé avec succès.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 3. Modifier un Exercice
    // ==========================================
    public function modifier(Request $request)
    {
        $sp_id = $this->getProprieteId($request); // 🟢 FIX
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $request->validate([
            'se_identifier' => 'required',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after:start_date',
            'period'        => 'required|string',
            'budget'        => 'required|numeric'
        ]);

        $exPropCol = Schema::hasColumn('exercices', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        $exercice = DB::table('exercices')
            ->where('se_identifier', $request->se_identifier)
            ->where($exPropCol, $sp_id)
            ->first();

        if (!$exercice) return response()->json(['success' => false, 'message' => "Exercice introuvable."], 404);

        if (strtolower($exercice->status) === 'clos') {
            return response()->json(['success' => false, 'message' => "Vous ne pouvez pas modifier un exercice déjà clos."], 400);
        }

        DB::beginTransaction();
        try {
            DB::table('exercices')
                ->where('se_identifier', $request->se_identifier)
                ->update([
                    'start_date' => $request->start_date,
                    'end_date'   => $request->end_date,
                    'period'     => $request->period,
                    'updated_at' => now()
                ]);

            DB::table('charges_previsionnelles')
                ->where('se_identifier', $request->se_identifier)
                ->update(['budget' => $request->budget, 'updated_at' => now()]);

            if ($request->has('budget_travaux')) {
                DB::table('charges_travaux')
                    ->where('se_identifier', $request->se_identifier)
                    ->update(['budget' => $request->budget_travaux, 'updated_at' => now()]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Exercice modifié avec succès.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 4. Supprimer un Exercice
    // ==========================================
    public function supprimer(Request $request)
    {
        $sp_id = $this->getProprieteId($request); // 🟢 FIX
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $request->validate(['se_identifier' => 'required']);

        $exPropCol = Schema::hasColumn('exercices', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        $exercice = DB::table('exercices')
            ->where('se_identifier', $request->se_identifier)
            ->where($exPropCol, $sp_id)
            ->first();

        if (!$exercice) return response()->json(['success' => false, 'message' => 'Exercice introuvable.'], 404);

        $hasEncaissements = DB::table('encaissements')->where('se_identifier', $request->se_identifier)->exists();
        $hasDepenses = DB::table('depenses')->where('se_identifier', $request->se_identifier)->exists();

        if ($hasEncaissements || $hasDepenses) {
            return response()->json(['success' => false, 'message' => 'Impossible de supprimer cet exercice car il contient des opérations financières (encaissements/dépenses).'], 400);
        }

        DB::beginTransaction();
        try {
            DB::table('charges_previsionnelles')->where('se_identifier', $request->se_identifier)->delete();
            DB::table('charges_travaux')->where('se_identifier', $request->se_identifier)->delete();
            DB::table('exercices')->where('se_identifier', $request->se_identifier)->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Exercice supprimé avec succès.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
        }
    }
}