<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    private function generateRealPdf(string $title, string $text): string
    {
        $title = preg_replace('/[^\x20-\x7E]/', '', $title);
        $text  = preg_replace('/[^\x20-\x7E]/', '', $text);
        $stream = "BT\n/F1 20 Tf\n50 780 Td\n(" . addslashes($title) . ") Tj\nET\n";
        $stream .= "BT\n/F2 12 Tf\n50 740 Td\n(" . addslashes($text) . ") Tj\nET\n";
        $pdf  = "%PDF-1.4\n1 0 obj <</Type /Catalog /Pages 2 0 R>> endobj\n";
        $pdf .= "2 0 obj <</Type /Pages /Kids [3 0 R] /Count 1>> endobj\n";
        $pdf .= "3 0 obj <</Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources <</Font <</F1 <</Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold>> /F2 <</Type /Font /Subtype /Type1 /BaseFont /Helvetica>> >> >> /Contents 4 0 R>> endobj\n";
        $pdf .= "4 0 obj <</Length " . strlen($stream) . ">> stream\n$stream\nendstream endobj\ntrailer <</Root 1 0 R>>\n%%EOF";
        return $pdf;
    }

    public function run()
    {
        // 1. CRÉATION DES TABLES MANQUANTES
        if (!Schema::hasTable('annonces')) {
            Schema::create('annonces', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('propriete_id');
                $table->string('title');
                $table->string('category')->nullable();
                $table->text('message');
                $table->date('expiration_date')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->uuidMorphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        // 2. NETTOYAGE DE LA BASE DE DONNÉES
        DB::table('notifications')->delete();
        DB::table('annonces')->delete();
        DB::table('appf_to_owner')->delete();
        if (Schema::hasTable('depense_for_owner')) DB::table('depense_for_owner')->delete();
        DB::table('encaissements')->delete();
        DB::table('depenses')->delete();
        DB::table('appels_fonds')->delete();
        DB::table('charges_previsionnelles')->delete();
        DB::table('exercices')->delete();
        DB::table('unit_to_key')->delete();
        DB::table('cle_repartitions')->delete();
        DB::table('user_owner_unit')->delete();
        DB::table('units')->delete();
        DB::table('user_as_owner')->delete();
        DB::table('proprietes')->delete();
        DB::table('users')->delete();

        // 3. CRÉATION DE LA PROPRIÉTÉ ET DE LA CLÉ DE RÉPARTITION
        $proprieteId = 'SP-MOBILE-001';
        DB::table('proprietes')->insert([
            'id' => $proprieteId, 
            'nom' => 'Résidence Les Jardins de l\'Océan', 
            'code_residence' => 'SYND-123456', // 🟢 ZEDNA LE CODE RÉSIDENCE HNA
            'siret' => '82902046874449', 
            'ville' => 'Casablanca', 
            'code_postal' => '20000', 
            'address' => 'Angle Boulevard Ghandi, Casablanca', 
            'created_at' => now(), 
            'updated_at' => now()
        ]);

        $cleId = DB::table('cle_repartitions')->insertGetId(['propriete_id' => $proprieteId, 'nom' => 'Charges Générales', 'tantiemes_total' => 1000, 'notes' => 'Clé principale de la résidence', 'created_at' => now(), 'updated_at' => now()]);

        // ========================================================
        // 4. CRÉATION DES SYNDICS (Nabil + Test) AVEC LEUR APPARTEMENT PERSO
        // ========================================================
        
        // Syndic 1 : Nabil Qadri (Directeur + Copro avec tantièmes 100)
        $syndic1 = User::create([
            'identifier' => 'SU-SYNDIC-001', 
            'full_name' => 'Nabil Qadri', 
            'email' => 'nabil.qadri@spherebleue.com', 
            'tel' => '0600000002', 
            'password' => Hash::make('123456'), 
            'agreed_on_terms' => true, 
            'status' => 'Actif',
            'photo' => asset('images/01.jpg') 
        ]);
        DB::table('user_as_owner')->insert(['user_id' => $syndic1->id, 'propriete_id' => $proprieteId, 'balance_prev' => 1500, 'status' => 1, 'role' => 'syndic', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_as_owner')->insert(['user_id' => $syndic1->id, 'propriete_id' => $proprieteId, 'balance_prev' => 1500, 'status' => 1, 'role' => 'coproprietaire', 'created_at' => now(), 'updated_at' => now()]);
        
        $syndicUnitId = DB::table('units')->insertGetId(['propriete_id' => $proprieteId, 'type' => 'Appartement', 'numero_porte' => 'B01', 'batiment' => 'A', 'etage' => '1er', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_owner_unit')->insert(['user_id' => $syndic1->id, 'unit_id' => $syndicUnitId, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('unit_to_key')->insert(['unit_id' => $syndicUnitId, 'cle_repartition_id'=> $cleId, 'tantieme' => 100, 'created_at' => now(), 'updated_at' => now()]);

        // Syndic 2 : Compte de test (App Store) + Copro avec tantièmes 100
        $syndic2 = User::create([
            'identifier' => 'SU-SYNDIC-002', 
            'full_name' => 'Syndic Test', 
            'email' => 'syndic@test.com', 
            'tel' => '0699999999', 
            'password' => Hash::make('123456'), 
            'agreed_on_terms' => true, 
            'status' => 'Actif',
            'photo' => 'https://randomuser.me/api/portraits/men/40.jpg' 
        ]);
        DB::table('user_as_owner')->insert(['user_id' => $syndic2->id, 'propriete_id' => $proprieteId, 'balance_prev' => 0, 'status' => 1, 'role' => 'syndic', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_as_owner')->insert(['user_id' => $syndic2->id, 'propriete_id' => $proprieteId, 'balance_prev' => 0, 'status' => 1, 'role' => 'coproprietaire', 'created_at' => now(), 'updated_at' => now()]);
        
        $testUnitId = DB::table('units')->insertGetId(['propriete_id' => $proprieteId, 'type' => 'Appartement', 'numero_porte' => 'B02', 'batiment' => 'B', 'etage' => '2ème', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_owner_unit')->insert(['user_id' => $syndic2->id, 'unit_id' => $testUnitId, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('unit_to_key')->insert(['unit_id' => $testUnitId, 'cle_repartition_id'=> $cleId, 'tantieme' => 100, 'created_at' => now(), 'updated_at' => now()]);

        // ========================================================

        // 5. CRÉATION DE MULTIPLES COPROPRIÉTAIRES (ACTIFS)
        $coprosData = [
            ['id' => 'CU-COPRO-001', 'name' => 'Test Copro', 'email' => 'rawanasmaa51@gmail.com', 'tel' => '0600000001', 'porte' => '12', 'bat' => 'A', 'etage' => '3', 'tantieme' => 200, 'balance' => 2300, 'photo' => 'https://randomuser.me/api/portraits/men/32.jpg'],
            ['id' => 'CU-COPRO-002', 'name' => 'Ahmed Alaoui', 'email' => 'ahmed@test.com', 'tel' => '0611111111', 'porte' => '14', 'bat' => 'A', 'etage' => '4', 'tantieme' => 200, 'balance' => 0, 'photo' => 'https://randomuser.me/api/portraits/men/46.jpg'],
            ['id' => 'CU-COPRO-003', 'name' => 'Sara Bennis', 'email' => 'sara@test.com', 'tel' => '0622222222', 'porte' => '1', 'bat' => 'B', 'etage' => 'RDC', 'tantieme' => 200, 'balance' => -500, 'photo' => 'https://randomuser.me/api/portraits/women/44.jpg'],
            ['id' => 'CU-COPRO-004', 'name' => 'Youssef Naciri', 'email' => 'youssef@test.com', 'tel' => '0633333333', 'porte' => '22', 'bat' => 'B', 'etage' => '5', 'tantieme' => 200, 'balance' => 1200, 'photo' => 'https://randomuser.me/api/portraits/men/75.jpg'],
        ];

        // N COLLECTE GA3 LES USERS (SYNDICS + COPROS ACTIFS)
        $allCopros = [$syndic1, $syndic2];
        foreach ($coprosData as $c) {
            $user = User::create([
                'identifier' => $c['id'], 
                'full_name' => $c['name'], 
                'email' => $c['email'], 
                'tel' => $c['tel'], 
                'password' => Hash::make('123456'), 
                'agreed_on_terms' => true, 
                'status' => 'Actif',
                'photo' => $c['photo']
            ]);
            $allCopros[] = $user;

            DB::table('user_as_owner')->insert(['user_id' => $user->id, 'propriete_id' => $proprieteId, 'balance_prev' => $c['balance'], 'status' => 1, 'role' => 'coproprietaire', 'created_at' => now(), 'updated_at' => now()]);
            
            $unitId = DB::table('units')->insertGetId(['propriete_id' => $proprieteId, 'type' => 'Appartement', 'numero_porte' => $c['porte'], 'batiment' => $c['bat'], 'etage' => $c['etage'], 'created_at' => now(), 'updated_at' => now()]);
            
            DB::table('user_owner_unit')->insert(['user_id' => $user->id, 'unit_id' => $unitId, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('unit_to_key')->insert(['unit_id' => $unitId, 'cle_repartition_id'=> $cleId, 'tantieme' => $c['tantieme'], 'created_at' => now(), 'updated_at' => now()]);
        }

        // ========================================================
        // 🟢 6. CRÉATION DES COPROPRIÉTAIRES EN ATTENTE (STATUS = 0)
        // ========================================================
        $pendingCoprosData = [
            ['id' => 'CU-PENDING-001', 'name' => 'Khadija Mansour', 'email' => 'khadija@test.com', 'tel' => '0644444444', 'photo' => 'https://randomuser.me/api/portraits/women/12.jpg'],
            ['id' => 'CU-PENDING-002', 'name' => 'Omar Chraibi', 'email' => 'omar@test.com', 'tel' => '0655555555', 'photo' => 'https://randomuser.me/api/portraits/men/22.jpg'],
        ];

        foreach ($pendingCoprosData as $p) {
            $pendingUser = User::create([
                'identifier' => $p['id'], 
                'full_name' => $p['name'], 
                'email' => $p['email'], 
                'tel' => $p['tel'], 
                'password' => Hash::make('123456'), 
                'agreed_on_terms' => true, 
                'status' => '0', // 🟢 EN ATTENTE (users table)
                'photo' => $p['photo']
            ]);

            // Link to the property with status = 0
            DB::table('user_as_owner')->insert([
                'user_id' => $pendingUser->id, 
                'propriete_id' => $proprieteId, 
                'balance_prev' => 0, 
                'status' => 0, // 🟢 EN ATTENTE (user_as_owner table)
                'role' => 'coproprietaire', 
                'created_at' => now(), 
                'updated_at' => now()
            ]);
        }

        // 7. EXERCICE & BUDGET
        $exerciseId = 'EX-MOBILE-2026';
        DB::table('exercices')->insert(['se_identifier' => $exerciseId, 'propriete_id' => $proprieteId, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'en cours', 'period' => 'Trimestre', 'created_at' => '2026-01-01 09:00:00', 'updated_at' => now()]);
        DB::table('charges_previsionnelles')->insert(['scp_identifier' => 'SCP-MOBILE-2026', 'se_identifier' => $exerciseId, 'budget' => 120000.00, 'total_encaissements' => 32500.00, 'total_depenses' => 24500.00, 'created_at' => '2026-01-01 09:00:00', 'updated_at' => now()]);

        // 8. APPELS DE FONDS (Ghadi tsstefa 3la gaç les users b'jouj: Syndics + Copros)
        $charges = [
            ['id' => 'AF-MOBILE-001', 'title' => 'Appel de charges T1 2026', 'amount' => 30000.00, 'due' => '2026-03-31', 'date' => '2026-01-15'],
            ['id' => 'AF-MOBILE-002', 'title' => 'Appel de charges T2 2026', 'amount' => 30000.00, 'due' => '2026-06-30', 'date' => '2026-04-15'],
            ['id' => 'AF-MOBILE-003', 'title' => 'Appel de charges T3 2026', 'amount' => 30000.00, 'due' => '2026-09-30', 'date' => '2026-07-15'],
            ['id' => 'AF-MOBILE-004', 'title' => 'Appel de charges T4 2026', 'amount' => 30000.00, 'due' => '2026-12-31', 'date' => '2026-10-15'],
        ];

        foreach ($charges as $charge) {
            $pdfPath = "proprietes/{$proprieteId}/appels_fonds/{$charge['id']}/{$charge['id']}.pdf";
            Storage::disk('public')->put($pdfPath, $this->generateRealPdf($charge['title'], "Montant global : {$charge['amount']} MAD"));
            
            DB::table('appels_fonds')->insert(['af_identifier' => $charge['id'], 'se_identifier' => $exerciseId, 'cle_repartition_id' => $cleId, 'type_charge' => 'previsionnel', 'sub_type' => 'planifie', 'title' => $charge['title'], 'amount' => $charge['amount'], 'due_date' => $charge['due'], 'is_generated' => true, 'is_sent' => true, 'created_at' => $charge['date'], 'updated_at' => now()]);
            
            // Tantièmes total = 1000 (Kol wahd 3ndo 100 awla 200 tantième)
            foreach ($allCopros as $user) {
                // Njibou tantieme dyal had l'user
                $unit = DB::table('user_owner_unit')->where('user_id', $user->id)->first();
                $tantieme = 100; // Par défaut
                if ($unit) {
                    $t = DB::table('unit_to_key')->where('unit_id', $unit->unit_id)->value('tantieme');
                    if ($t) $tantieme = $t;
                }

                $montantCopro = ($charge['amount'] * $tantieme) / 1000;
                DB::table('appf_to_owner')->insert(['af_identifier' => $charge['id'], 'user_id' => $user->id, 'montant_du' => $montantCopro, 'created_at' => $charge['date'], 'updated_at' => now()]);
            }
        }

        // 9. ENCAISSEMENTS
        $paiements = [
            ['id' => 'SEN-MOBILE-001', 'owner_id' => $syndic1->id, 'date' => '2026-01-25', 'amount' => 2500.00, 'method' => 'virement', 'ref' => 'VIR-NABIL-01'],
            ['id' => 'SEN-MOBILE-002', 'owner_id' => $allCopros[2]->id, 'date' => '2026-02-10', 'amount' => 7500.00, 'method' => 'cheque', 'ref' => 'CHQ-AHMED-01'],
            ['id' => 'SEN-MOBILE-003', 'owner_id' => $allCopros[3]->id, 'date' => '2026-04-20', 'amount' => 5000.00, 'method' => 'virement', 'ref' => 'VIR-SARA-01'],
        ];

        foreach ($paiements as $p) {
            $pdfPath = "proprietes/{$proprieteId}/encaissements/{$p['id']}.pdf";
            Storage::disk('public')->put($pdfPath, $this->generateRealPdf('Recu de Paiement', "Paiement {$p['amount']} MAD - {$p['method']}"));
            DB::table('encaissements')->insert(['sen_identifier' => $p['id'], 'se_identifier' => $exerciseId, 'owner_id' => $p['owner_id'], 'title' => 'Paiement - ' . $p['ref'], 'amount' => $p['amount'], 'date' => $p['date'], 'type_charges' => 'previsionnel', 'sub_type_charges' => $p['method'], 'document_url' => '/storage/' . $pdfPath, 'created_at' => $p['date'], 'updated_at' => now()]);
        }

        // 10. DÉPENSES
        $depenses = [
            ['id' => 'DEP-MOBILE-001', 'title' => 'Réparation Ascenseur Bâtiment A', 'amount' => 1500.00, 'date' => '2026-05-02', 'category' => 'maintenance'],
            ['id' => 'DEP-MOBILE-002', 'title' => 'Nettoyage des parties communes (Août)', 'amount' => 800.00, 'date' => '2026-08-05', 'category' => 'entretien'],
        ];

        foreach ($depenses as $d) {
            DB::table('depenses')->insert([
                'sdep_identifier' => $d['id'], 'se_identifier' => $exerciseId, 'cle_repartition_id' => $cleId, 'title' => $d['title'], 'amount' => $d['amount'], 'date' => $d['date'], 'type_charges' => 'previsionnel', 'sub_type_charges' => $d['category'], 'created_at' => $d['date'], 'updated_at' => now(),
            ]);
        }

        // 11. DOCUMENTS
        foreach (['assemblees', 'factures', 'reglements', 'charges', 'autres'] as $f) { Storage::disk('public')->makeDirectory("proprietes/{$proprieteId}/{$f}"); }
        Storage::disk('public')->put("proprietes/{$proprieteId}/reglements/Reglement_Copropriete.pdf", $this->generateRealPdf('Reglement de Copropriete', 'Reglement general de la residence.'));
        Storage::disk('public')->put("proprietes/{$proprieteId}/factures/Facture_Lydec_Sept.pdf", $this->generateRealPdf('Facture Lydec', 'Montant : 1250 MAD.'));
        Storage::disk('public')->put("proprietes/{$proprieteId}/assemblees/PV_AGO_2026.pdf", $this->generateRealPdf('Proces Verbal AGO 2026', 'Validation du budget previsionnel 2026.'));

        // 12. ANNONCES MULTIPLES
        DB::table('annonces')->insert([
            ['propriete_id' => $proprieteId, 'title' => 'Bienvenue sur Syndify', 'category' => 'Information', 'message' => 'L\'application est désormais disponible pour tous les résidents.', 'created_at' => '2026-09-01 10:00:00', 'updated_at' => now()],
            ['propriete_id' => $proprieteId, 'title' => 'Coupure d\'eau Bâtiment B', 'category' => 'Urgent', 'message' => 'Une coupure d\'eau aura lieu ce Jeudi de 14h à 16h.', 'created_at' => '2026-09-04 09:00:00', 'updated_at' => now()]
        ]);

        // 13. NOTIFICATIONS
        DB::table('notifications')->insert([
            ['id' => Str::uuid(), 'type' => 'App\Notifications\NouvelleActiviteNotification', 'notifiable_type' => 'App\Models\User', 'notifiable_id' => $syndic1->id, 'data' => json_encode(['titre' => 'Nouvel appel de charges', 'message' => 'Votre appel de charges T3 2026 est disponible.', 'type' => 'charge']), 'read_at' => null, 'created_at' => Carbon::parse('2026-09-07 10:00:00'), 'updated_at' => Carbon::parse('2026-09-07 10:00:00')],
            ['id' => Str::uuid(), 'type' => 'App\Notifications\NouvelleActiviteNotification', 'notifiable_type' => 'App\Models\User', 'notifiable_id' => $syndic1->id, 'data' => json_encode(['titre' => 'Paiement reçu', 'message' => 'Votre paiement a été validé.', 'type' => 'paiement']), 'read_at' => null, 'created_at' => Carbon::parse('2026-02-10 11:00:00'), 'updated_at' => Carbon::parse('2026-02-10 11:00:00')]
        ]);

        $this->command->info('');
        $this->command->info('==============================================');
        $this->command->info('      SYNDIFY SEEDER AVEC DATA POUR NABIL');
        $this->command->info('==============================================');
    }
}