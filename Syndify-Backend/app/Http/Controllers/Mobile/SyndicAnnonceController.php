<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class SyndicAnnonceController extends Controller
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
    // 1. ÉCRAN 17: Liste des annonces
    // ==========================================
    public function index(Request $request)
    {
        try {
            $propId = $this->getProprieteId($request->user());
            if (!$propId) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

            // إذا لم يكن الجدول موجوداً بعد، نعيد قائمة فارغة
            if (!Schema::hasTable('annonces')) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $annonces = DB::table('annonces')
                ->where('propriete_id', $propId)
                ->orderBy('created_at', 'desc')
                ->get();

            $formatted = $annonces->map(function ($a) {
                $cat = strtolower($a->category ?? 'information');
                $color = '#2196F3'; // Blue
                $icon = 'info';

                if (str_contains($cat, 'travaux')) { $color = '#FF9800'; $icon = 'build'; }
                elseif (str_contains($cat, 'urgent')) { $color = '#F44336'; $icon = 'warning'; }
                elseif (str_contains($cat, 'assemblée') || str_contains($cat, 'reunion')) { $color = '#9C27B0'; $icon = 'groups'; }

                return [
                    'id' => $a->id,
                    'title' => $a->title,
                    'category' => ucfirst($a->category ?? 'Information'),
                    'date' => Carbon::parse($a->created_at)->format('d/m/Y'),
                    'message' => $a->message,
                    'colorHex' => $color,
                    'iconString' => $icon,
                ];
            });

            return response()->json(['success' => true, 'data' => $formatted], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 2. ÉCRAN 18: Créer une annonce
    // ==========================================
  public function store(Request $request)
    {
        // 1. 🟢 Ajout de la validation pour la date
        $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
            'category' => 'required|string',
            'expiration_date' => 'nullable|date_format:Y-m-d', // Doit être une date valide ou null
        ]);

        DB::beginTransaction();
        try {
            $propId = $this->getProprieteId($request->user());
            if (!$propId) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

            if (!Schema::hasTable('annonces')) {
                Schema::create('annonces', function (Blueprint $table) {
                    $table->id();
                    $table->string('propriete_id');
                    $table->string('title');
                    $table->string('category')->nullable();
                    $table->text('message');
                    $table->date('expiration_date')->nullable();
                    $table->timestamps();
                });
            }

            // 1. تسجيل الإعلان
            DB::table('annonces')->insert([
                'propriete_id' => $propId,
                'title' => $request->title,
                'category' => $request->category,
                'message' => $request->message,
                'expiration_date' => $request->expiration_date ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

         // 3. 🟢 ENVOI DES PUSH NOTIFICATIONS VIA FIREBASE
            if ($request->send_notification) {
                
                // 🟢 LA CORRECTION EST ICI : On définit la colonne avant de l'utiliser
                $propOwnerCol = $this->col('user_as_owner', 'propriete_id', 'sp_identifier');

                // Jib ga3 les utilisateurs li 3ndhom fcm_token f had la propriété
                $fcmTokens = DB::table('users')
                    ->join('user_as_owner', 'users.id', '=', 'user_as_owner.user_id')
                    ->whereRaw("user_as_owner.{$propOwnerCol}::text = ?", [$propId])
                    ->whereNotNull('users.fcm_token')
                    ->pluck('users.fcm_token')
                    ->toArray();

                if (!empty($fcmTokens)) {
                    foreach ($fcmTokens as $fcmToken) {
                        try {
                            Http::withHeaders([
                                'Authorization' => 'Bearer ' . env('FIREBASE_SERVER_KEY'), 
                                'Content-Type' => 'application/json',
                            ])->post('https://fcm.googleapis.com/v1/projects/notificationsyndify/messages:send', [
                                'message' => [
                                    'token' => $fcmToken,
                                    'notification' => [
                                        'title' => 'Nouvelle annonce : ' . $request->title,
                                        'body' => Str::limit($request->message, 50),
                                    ],
                                ],
                            ]);
                        } catch (\Exception $ex) {
                            // Ignorer l'erreur d'un token
                            continue;
                        }
                    }
                }
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Une erreur est survenue lors de la création de l\'annonce.'], 500);
        }
        DB::commit();
        return response()->json(['success' => true, 'message' => 'Annonce créée avec succès.']);
    }
}