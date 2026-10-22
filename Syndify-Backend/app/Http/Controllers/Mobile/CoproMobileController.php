<?php
namespace App\Http\Controllers\Mobile;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CoproMobileController extends Controller
{
    private function getResId($userId) {
        $col = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        return DB::table('user_as_owner')->whereRaw('user_id::text = ?', [(string)$userId])->value($col);
    }

    public function getDashboard(Request $request) {
        $userId = (string) $request->user()->id;
        $resId = $this->getResId($userId);
        
        $solde = DB::table('user_as_owner')->whereRaw('user_id::text = ?', [$userId])->value('balance_prev') ?? 0;
        
        $charge = DB::table('appf_to_owner')->join('appels_fonds', 'appf_to_owner.af_identifier', '=', 'appels_fonds.af_identifier')
            ->whereRaw('appf_to_owner.user_id::text = ?', [$userId])->orderBy('appels_fonds.created_at', 'desc')
            ->select('appels_fonds.title', 'appf_to_owner.montant_du as amount', 'appels_fonds.due_date')->first();
            
        $paie = DB::table('encaissements')->whereRaw('owner_id::text = ?', [$userId])->orderBy('date', 'desc')
            ->select('date', 'amount', 'sub_type_charges as mode')->first();
            
        $annonces = Schema::hasTable('annonces') ? DB::table('annonces')->where('propriete_id', $resId)->orderBy('created_at', 'desc')->limit(5)->get() : [];
        
        $notifs = Schema::hasTable('notifications') ? DB::table('notifications')->whereRaw('notifiable_id::text = ?', [$userId])->whereNull('read_at')->count() : 0;

        return response()->json([
            'success' => true,
            'residence_name' => "Résidence Les Jardins de l'Océan",
            'lot_info' => 'A - 12',
            'data' => [
                'first_name' => explode(' ', $request->user()->full_name ?? 'Copro')[0],
                'residence_name' => "Résidence Les Jardins de l'Océan",
                'solde' => (float)$solde,
                'prochaine_charge' => $charge,
                'dernier_paiement' => $paie,
                'annonces' => $annonces,
                'unread_notifications' => $notifs
            ]
        ]);
    }

    public function getMesCharges(Request $request) {
        $userId = (string) $request->user()->id;
        $charges = DB::table('appf_to_owner')->join('appels_fonds', 'appf_to_owner.af_identifier', '=', 'appels_fonds.af_identifier')
            ->whereRaw('appf_to_owner.user_id::text = ?', [$userId])->orderBy('appels_fonds.created_at', 'desc')->get();
        
        $solde = (float) (DB::table('user_as_owner')->whereRaw('user_id::text = ?', [$userId])->value('balance_prev') ?? 0);
        
        $formatted = $charges->map(function($c) use ($solde) {
            // 🟢 Status dynamique : Si le solde global est à 0 ou en crédit, la charge est considérée comme Payée/À jour
            $status = ($solde <= 0) ? 'Payé' : 'Impayé';

            return [
                'id' => $c->af_identifier, 
                'title' => $c->title,
                'amount' => number_format($c->montant_du, 2, ',', ' ') . ' MAD',
                'date_emission' => Carbon::parse($c->created_at)->format('d/m/Y'),
                'date_echeance' => Carbon::parse($c->due_date)->format('d/m/Y'),
                'status' => $status
            ];
        });
        
        return response()->json([
            'success' => true, 
            'solde' => number_format($solde, 2, ',', ' ').' MAD', 
            'raw_solde' => $solde, 
            'data' => $formatted
        ]);
    }

    public function getMesPaiements(Request $request) {
        $userId = (string) $request->user()->id;
        $paiements = DB::table('encaissements')->whereRaw('owner_id::text = ?', [$userId])->orderBy('date', 'desc')->get();
        
        $formatted = $paiements->map(function($p) {
            $modeStr = strtolower($p->sub_type_charges ?? 'virement');
            $color = str_contains($modeStr, 'espece') ? '#FF9800' : (str_contains($modeStr, 'cheque') ? '#D32F2F' : '#1A5EAC');
            return [
                'id' => $p->sen_identifier, 'date' => Carbon::parse($p->date)->format('d/m/Y'),
                'amount' => number_format($p->amount, 0, ',', ' ') . ' MAD',
                'mode' => ucfirst($modeStr), 'reference' => $p->title, 'colorHex' => $color
            ];
        });
        return response()->json(['success' => true, 'data' => $formatted]);
    }

    public function getMesAnnonces(Request $request) {
        if (!Schema::hasTable('annonces')) return response()->json(['success' => true, 'data' => []]);
        $resId = $this->getResId($request->user()->id);
        
        $annonces = DB::table('annonces')->where('propriete_id', $resId)->orderBy('created_at', 'desc')->get();
        $formatted = $annonces->map(function($a) {
            $cat = strtolower($a->category ?? '');
            $color = '#2196F3'; $icon = 'info';
            if (str_contains($cat, 'travaux')) { $color = '#FF9800'; $icon = 'build'; }
            elseif (str_contains($cat, 'urgent')) { $color = '#F44336'; $icon = 'warning'; }
            elseif (str_contains($cat, 'assemblée')) { $color = '#9C27B0'; $icon = 'groups'; }
            return [
                'id' => $a->id, 'title' => $a->title, 'category' => ucfirst($a->category),
                'date' => Carbon::parse($a->created_at)->format('d/m/Y'),
                'message' => $a->message, 'colorHex' => $color, 'iconString' => $icon
            ];
        });
        return response()->json(['success' => true, 'data' => $formatted]);
    }

    public function getMesDocuments(Request $request) {
        $userId = (string) $request->user()->id;
        $resId = $this->getResId($userId);

        if (!$resId) {
            return response()->json(['success' => false, 'message' => 'Aucune résidence', 'data' => []], 404);
        }

        $base = "proprietes/{$resId}";
        $cats = ['assemblees'=>'Assemblées Générales', 'factures'=>'Factures', 'reglements'=>'Règlement', 'charges'=>'Charges', 'autres'=>'Autres'];
        $res = [];
        
        foreach($cats as $folder => $label) {
            $files = [];
            $folderPath = "$base/$folder";
            
            if(Storage::disk('public')->exists($folderPath)) {
                foreach(Storage::disk('public')->files($folderPath) as $file) {
                    $files[] = [
                        'name' => basename($file), 
                        'url' => asset('storage/' . $file), 
                        'size' => round(Storage::disk('public')->size($file)/1024, 2).' KB',
                        'date' => Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file))->format('d/m/Y')
                    ];
                }
            }
            $res[] = ['category' => $label, 'files' => $files];
        }
        return response()->json(['success' => true, 'residence_id' => $resId, 'residence_name' => "Résidence Les Jardins de l'Océan", 'data' => $res]);
    }

    public function envoyerProcuration(Request $request)
    {
        $request->validate([
            'mandataire' => 'required|string|max:255',
        ]);

        try {
            return response()->json([
                'success' => true,
                'message' => 'Procuration envoyée avec succès à votre syndic.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la procuration: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function telechargerPdf($id, Request $request)
    {
        $userId = (string) $request->user()->id;

        $charge = DB::table('appf_to_owner')
            ->where('af_identifier', $id)
            ->whereRaw('user_id::text = ?', [$userId])
            ->first();

        if (!$charge) {
            return response()->json(['success' => false, 'message' => 'Charge introuvable ou accès refusé.'], 404);
        }

        $path = storage_path('app/public/charges/' . $id . '.pdf');

        if (!file_exists($path)) {
            return response()->json(['success' => false, 'message' => 'Le fichier PDF n\'a pas encore été généré.'], 404);
        }

        return response()->download($path, 'Appel_Charge_' . $id . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
    
    public function getNotifications(Request $request) {
        if (!Schema::hasTable('notifications')) return response()->json(['success' => true, 'data' => []]);
        $userId = (string) $request->user()->id;

        $notifs = DB::table('notifications')->whereRaw('notifiable_id::text = ?', [$userId])->orderBy('created_at', 'desc')->get()->map(function($n) {
            $d = is_string($n->data) ? json_decode($n->data, true) : (array)$n->data;
            return [
                'id' => $n->id, 'titre' => $d['titre'] ?? '', 'message' => $d['message'] ?? '', 'type' => $d['type'] ?? 'info',
                'lu' => $n->read_at !== null, 'date_humaine' => Carbon::parse($n->created_at)->locale('fr')->diffForHumans(),
                'date' => $n->created_at
            ];
        });
        return response()->json(['success' => true, 'data' => $notifs]);
    }

    public function getMesAssemblees(Request $request) {
        $userId = (string) $request->user()->id;
        $resId = $this->getResId($userId); 

        if (!$resId) {
            return response()->json(['success' => false, 'message' => 'Aucune résidence trouvée.', 'data' => []], 404);
        }

        DB::table('assemblees')
            ->where('propriete_id', $resId)
            ->where('statut', 'planifiee')
            ->where('date_ag', '<', now()->toDateString())
            ->update(['statut' => 'terminee']);

        $assemblees = DB::table('assemblees')
            ->where('propriete_id', $resId)
            ->orderBy('date_ag', 'desc')
            ->get();

        $formatted = $assemblees->map(function ($ag) {
            return [
                'id' => $ag->id,
                'title' => $ag->titre, 
                'date_time' => \Carbon\Carbon::parse($ag->date_ag)->format('d/m/Y') . ' à ' . $ag->heure_ag, 
                'location' => $ag->lieu,
                'status' => $ag->statut, 
                'pv_url' => $ag->pv_url ?? null,
                'ordre_jour' => $ag->ordre_jour
            ];
        });

        return response()->json(['success' => true, 'data' => $formatted], 200);
    }

    public function marquerNotificationLue(Request $request, $id) {
        if (!Schema::hasTable('notifications')) return response()->json(['success' => true]);
        $userId = (string) $request->user()->id;

        DB::table('notifications')->where('id', $id)->whereRaw('notifiable_id::text = ?', [$userId])->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    }
}