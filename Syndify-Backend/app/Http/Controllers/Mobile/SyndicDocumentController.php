<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyndicDocumentController extends Controller
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
    // 1. ÉCRAN 15: Liste des documents par catégorie
    // ==========================================
    public function index(Request $request)
    {
        try {
            $propId = $this->getProprieteId($request->user());
            if (!$propId) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

            $basePath = "proprietes/{$propId}";
            
            $categories = [
                'assemblees' => 'Assemblées générales',
                'factures'   => 'Factures',
                'reglements' => 'Règlement',
                'charges'    => 'Charges',
                'autres'     => 'Autres'
            ];

            foreach (array_keys($categories) as $folder) {
                if (!Storage::disk('public')->exists("{$basePath}/{$folder}")) {
                    Storage::disk('public')->makeDirectory("{$basePath}/{$folder}");
                }
            }

            $groupedDocuments = [];

            foreach ($categories as $folder => $label) {
                $files = Storage::disk('public')->files("{$basePath}/{$folder}");
                $folderContent = [];

                foreach ($files as $file) {
                    $folderContent[] = [
                        'name' => basename($file),
                        'path' => $file,
                        // 🟢 FIX : On s'assure du bon format de l'URL pour Flutter
                        'url'  => asset('storage/' . str_replace('\\', '/', $file)), 
                        'size' => $this->formatBytes(Storage::disk('public')->size($file)),
                        'date' => \Carbon\Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file))->format('d/m/Y')
                    ];
                }

                $groupedDocuments[] = [
                    'category' => $label,
                    'files'    => $folderContent
                ];
            }

            return response()->json([
                'success' => true,
                'data'    => $groupedDocuments
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 2. ÉCRAN 16: Ajouter (Upload) un document
    // ==========================================
    public function store(Request $request)
    {
        $request->validate([
            'nom'       => 'required|string|max:255',
            'categorie' => 'required|string',
            'fichier'   => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240' 
        ]);

        try {
            $propId = $this->getProprieteId($request->user());
            if (!$propId) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

            $folderMap = [
                'Assemblées générales' => 'assemblees',
                'Factures'             => 'factures',
                'Règlement'            => 'reglements',
                'Charges'              => 'charges',
                'Autres'               => 'autres'
            ];

            $folder = $folderMap[$request->categorie] ?? 'autres';
            $destinationPath = "proprietes/{$propId}/{$folder}";

            $extension = $request->file('fichier')->getClientOriginalExtension();
            $safeName = Str::slug($request->nom) . '_' . time() . '.' . $extension;

            $path = $request->file('fichier')->storeAs($destinationPath, $safeName, 'public');
            
            return response()->json([
                'success' => true, 
                'message' => 'Document publié avec succès.',
                // 🟢 FIX : Pareil pour l'upload, on retourne la bonne URL
                'url'     => asset('storage/' . str_replace('\\', '/', $path))
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }
}