<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyndicAssembleeController extends Controller
{
    // ==========================================
    // Helper : Récupérer l'ID de la résidence
    // ==========================================
    private function getProprieteId($user) {
        $ownerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        $propCol  = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';

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
    // 1. Lister les Assemblées
    // ==========================================
    public function index(Request $request)
    {
        try {
            $propId = $this->getProprieteId($request->user());
            if (!$propId) {
                return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
            }

            // Récupérer et mettre à jour le statut automatiquement si la date est passée
            DB::table('assemblees')
                ->where('propriete_id', $propId)
                ->where('statut', 'planifiee')
                ->where('date_ag', '<', now()->toDateString())
                ->update(['statut' => 'terminee']);

            $assemblees = DB::table('assemblees')
                ->where('propriete_id', $propId)
                ->orderBy('date_ag', 'desc')
                ->get();

            // Formatage pour l'application Flutter
            $formatted = $assemblees->map(function ($ag) {
                return [
                    'id' => $ag->id,
                    'titre' => $ag->titre,
                    'date' => \Carbon\Carbon::parse($ag->date_ag)->format('Y-m-d'),
                    'heure' => $ag->heure_ag,
                    'lieu' => $ag->lieu,
                    'ordre_jour' => $ag->ordre_jour,
                    'statut' => $ag->statut, // 'planifiee' ou 'terminee'
                    'pv_url' => $ag->pv_url
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formatted
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 2. Créer une nouvelle Assemblée (Planifier)
    // ==========================================
    public function store(Request $request)
    {
        $request->validate([
            'titre' => 'required|string|max:255',
            'date' => 'required|date',
            'heure' => 'required|string',
            'lieu' => 'required|string|max:255',
            'ordre_jour' => 'required|string',
        ]);

        try {
            $propId = $this->getProprieteId($request->user());
            if (!$propId) {
                return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
            }

            // Enregistrement dans la base de données
            $agId = DB::table('assemblees')->insertGetId([
                'propriete_id' => $propId,
                'created_by' => $request->user()->id,
                'titre' => $request->titre,
                'date_ag' => $request->date,
                'heure_ag' => $request->heure,
                'lieu' => $request->lieu,
                'ordre_jour' => $request->ordre_jour,
                'statut' => 'planifiee',
                'is_notified' => $request->notifier_residents ?? false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 🟢 TODO : Logique d'envoi de notification PUSH (Firebase) aux copropriétaires si $request->notifier_residents est true.

            return response()->json([
                'success' => true,
                'message' => 'Assemblée générale planifiée avec succès.'
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}