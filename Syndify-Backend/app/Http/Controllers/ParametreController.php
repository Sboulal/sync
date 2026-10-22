<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ParametreController extends Controller
{
private function getProprieteId(Request $request)
{
    $userId = auth()->id(); 
    if (!$userId) return null; 

    $ownerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

    $residenceId = $request->header('residence_id') ?? $request->input('residence_id') ?? $request->cookie('residence_id');

    if ($residenceId) {
        $link = DB::table('user_as_owner')
            ->where('user_id', (string)$userId)
            ->where($ownerCol, $residenceId)
            ->first();
        if ($link) return $residenceId;
    }

    $link = DB::table('user_as_owner')->where('user_id', $userId)->first();
    return $link ? $link->$ownerCol : null;
}
// ==========================================
    // 1. Jbed les infos l-qdam bash n-3emmrou l-Formulaire
    // ==========================================
    public function getParametres(Request $request)
    {
        $sp_id = $this->getProprieteId($request);
        $userId = auth()->id();

        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $propCol = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
        
        $user = DB::table('users')->where('id', $userId)->first();
        $propriete = DB::table('proprietes')->where($propCol, $sp_id)->first();

        // 🟢 هنا كنجينيريو SIRET عشوائي (14 رقم) إيلا كان خاوي فـ Database
        $siret = $propriete->siret;
        if (empty($siret) && Schema::hasColumn('proprietes', 'siret')) {
            $siret = rand(1000000, 9999999) . rand(1000000, 9999999); // كيعطينا 14 رقم عشوائي
            // كنسجلوه فـ Database باش يبقى ديما
            DB::table('proprietes')->where($propCol, $sp_id)->update(['siret' => $siret]);
        }

        return response()->json([
            'success' => true,
            'syndic' => [
                'nom' => $user->full_name ?? '',
                'telephone' => $user->tel ?? '',
                'email' => $user->email ?? ''
            ],
            'residence' => [
                'nom' => $propriete->nom ?? 'Résidence',
                'adresse' => $propriete->address ?? '',
                'ville' => $propriete->ville ?? '', 
                'code_postal' => $propriete->code_postal ?? '',
                'siret' => $siret // 🟢 كنصيفطو السيريت الجديد للواجهة
            ]
        ]);
    }

    // ==========================================
    // 2. Sauvegardy les modifications
    // ==========================================
    public function updateParametres(Request $request)
    {
        $sp_id = $this->getProprieteId($request);
        $userId = auth()->id();

        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        // Vérification l-khfifa
        $request->validate([
            'syndic.nom' => 'required|string',
            'syndic.email' => 'required|email',
            'residence.nom' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            // 1. Mise à jour dyal l-User (Syndic)
            DB::table('users')->where('id', $userId)->update([
                'full_name' => $request->input('syndic.nom'),
                'tel' => $request->input('syndic.telephone'),
                'email' => $request->input('syndic.email'),
                'updated_at' => now()
            ]);

            // 2. Mise à jour dyal l-Résidence
            $propCol = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
            $updateData = [
                'nom' => $request->input('residence.nom'),
                'address' => $request->input('residence.adresse'),
                'updated_at' => now()
            ];

            // 🟢 Vérifiw wesh kaynin had les colonnes f DB 9bel ma n-siftohom
            if (Schema::hasColumn('proprietes', 'ville')) {
                $updateData['ville'] = $request->input('residence.ville');
            }
            if (Schema::hasColumn('proprietes', 'code_postal')) {
                $updateData['code_postal'] = $request->input('residence.code_postal');
            }
            // 🟢 زدنا Siret هنا باش يتسجل فـ قاعدة البيانات
            if (Schema::hasColumn('proprietes', 'siret')) {
                $updateData['siret'] = $request->input('residence.siret');
            }

            DB::table('proprietes')->where($propCol, $sp_id)->update($updateData);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Paramètres enregistrés avec succès.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }
}