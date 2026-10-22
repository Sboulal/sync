# Syndify
sudo chown -R salmasb:salmasb /home/salmasb/Syndify/Syndify-Backend                
sudo chmod -R 775 /home/salmasb/Syndify/Syndify-Backend

belongsToMany()
L-Tables pivots (bp_to_key w bt_to_key) ma-kay7tajouch Model khass bihom, 7it Eloquent (ORM dyal Laravel) kay-gérerhom automatiquement b la méthode belongsToMany() lli zedt lik f l-Models l-fo9.



 sudo docker exec -it syndify_backend php artisan migrate:fresh --seed --seeder=SyndifyTestDataSeeder

  Dropping all tables ....................................................................................................... 33.17ms DONE

   INFO  Preparing database.  

  Creating migration table ................................................................................................... 8.19ms DONE

   INFO  Running migrations.  

  2026_04_13_141618_create_users_table ....................................................................................... 7.75ms DONE
  2026_04_13_141702_create_proprietes_table .................................................................................. 3.69ms DONE
  2026_04_13_141721_create_user_as_owner_table ............................................................................... 5.35ms DONE
  2026_04_13_141742_create_units_table ....................................................................................... 4.22ms DONE
  2026_04_13_141756_create_user_owner_unit_table ............................................................................. 3.86ms DONE
  2026_04_13_141813_create_cle_repartitions_table ............................................................................ 3.94ms DONE
  2026_04_13_141830_create_unit_to_key_table ................................................................................. 3.71ms DONE
  2026_04_13_144235_create_cache_table ....................................................................................... 7.60ms DONE
  2026_04_15_112855_create_exercices_table ................................................................................... 4.06ms DONE
  2026_04_15_112909_create_charge_previsionnelles_table ...................................................................... 4.10ms DONE
  2026_04_15_112922_create_charge_travauxes_table ............................................................................ 3.77ms DONE
  2026_04_15_112934_create_bp_to_key_table ................................................................................... 3.62ms DONE
  2026_04_15_112948_create_bt_to_key_table ................................................................................... 3.39ms DONE
  2026_04_15_155106_add_propriete_id_to_units_table .......................................................................... 2.35ms DONE
  2026_04_16_104420_create_encaissements_table ............................................................................... 5.96ms DONE
  2026_04_16_104437_create_depenses_table .................................................................................... 5.34ms DONE
  2026_04_16_104456_create_depense_for_owner_table ........................................................................... 3.59ms DONE
  2026_04_21_081921_create_appels_fonds_tables .............................................................................. 14.30ms DONE
  2026_04_21_150502_add_solde_to_user_as_owner_table ......................................................................... 1.18ms DONE
  2026_04_22_091803_add_otp_columns_to_users_table ........................................................................... 8.87ms DONE
  2026_04_22_094206_create_personal_access_tokens_table ...................................................................... 7.90ms DONE
  2026_04_23_114348_create_clotures_table .................................................................................... 5.02ms DONE


   INFO  Seeding database.  

🚀 BOOM! Données 100% liées et logiques!
⏳ Création des Documents PDF en cours...
🚀 BOOM! Documents générés et Data liée!
➜  Syndify git:(main) ✗ sudo docker exec -it syndify_backend php artisan tinker
Psy Shell v0.12.22 (PHP 8.4.20 — cli) by Justin Hileman
New PHP manual is available (latest: 3.0.5). Update with `doc --update-manual`

>  DB::table('users')->first()->email;

= "afay@example.org"

> 




/* L-importation dyal Tailwind v4 w Google Fonts */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
@import "tailwindcss";

/* Configuration dyal les couleurs */
@theme {
  /* 🟢 HADA L-7EL: Rddina l-Khder l-asli lli khtariti (#4E8263) */
  --color-syndify-dark: #4E8263;
  --color-syndify-light: #F1F4F9; 
  --color-syndify-bg: #F8FAFC;
  --color-syndify-text: #111815;
  --color-syndify-accent: #F4B840; /* L-Limouni */
}

body {
  background-color: var(--color-syndify-bg);
  color: var(--color-syndify-text);
}

@layer components {

  /* ==========================================
     🟢 SYNDIFY GLOBAL BUTTONS
     ========================================== */
  .btn-primary {
    @apply flex items-center justify-center gap-2 px-4 py-2.5 text-[13px] font-semibold bg-syndify-accent text-white border-0 cursor-pointer shadow-none rounded-lg transition-all hover:brightness-105 outline-none;
  }
  
  /* 🟢 L-BOUTON DYAL "ANNULER" */
  .btn-secondary {
    @apply flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-[13px] text-gray-600 bg-white transition-colors cursor-pointer shadow-none outline-none;
  }

  .btn-transparent {
    @apply flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg font-semibold text-[13px] bg-transparent text-white transition-all cursor-pointer shadow-none outline-none;
  }

  /* ==========================================
     🟢 SYNDIFY GLOBAL TABLES (Design Unifié)
     ========================================== */
  
  /* L-cadre l-byed lli kay-jme3 l-tableau */
  .syndify-table-container {
    @apply bg-white rounded-xl border border-gray-200 shadow-none overflow-hidden w-full flex flex-col relative;
  }

  /* L-Tableau b-7ed dato */
  .syndify-table {
    @apply w-full text-left border-collapse;
  }

  /* 🟢 Header w Footer dyal l-Tableau */
  .syndify-table-thead,
  .syndify-table-tfoot {
    @apply bg-gray-200 border-b-2 border-gray-200;
  }

  /* Les Titres dyal l-Tableau (TH) */
  .syndify-table-th {
    @apply py-3 px-6 font-bold text-gray-800 text-[13.5px];
  }
  
  /* L-Wesset dyal l-Tableau (Tbody) */
  .syndify-table-tbody {
    @apply text-[13px] font-semibold text-gray-700 ;
  }

  /* S-stoura dyal l-Tableau (TR) */
  .syndify-table-tr {
    @apply border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors;
  }

  /* 🟢 L-Khanat dyal l-Tableau (TD) */
  .syndify-table-td {
    @apply py-3 px-6 align-middle;
  }

  /* ==========================================
     🟢 SYNDIFY TABLE: COLONNES L-KBAR (Text Twil)
     ========================================== */
  
  /* Khana 3rida f l-Header (TH) */
  .syndify-table-th-long {
    @apply py-4 px-6 font-bold text-gray-800 text-[13.5px] min-w-[280px] w-[40%];
  }

  /* Khana 3rida f l-Wset (TD) m3a retour à la ligne */
  .syndify-table-td-long {
    @apply py-4 px-6 align-middle min-w-[280px] whitespace-normal leading-relaxed;
  }

  /* ==========================================
     🟢 SYNDIFY GLOBAL TABS (Navigation)
     ========================================== */
  
  /* L-Banda l-bida lli l-fou9 shadda l-3erd kamel */
  .syndify-tabs-container {
    @apply px-6 md:px-8 border-b border-gray-200 bg-white h-[54px] shrink-0;
  }

  /* L-Mjmou3a lli jam3a les boutons */
  .syndify-tabs-wrapper {
    @apply flex items-center gap-8 h-full;
  }

  /* L-Bouton dyal l-Menu (Assass) */
  .syndify-tab {
    @apply h-full flex items-center text-[14px] border-b-[3px] transition-colors cursor-pointer pt-[3px] outline-none bg-transparent;
  }

  /* L-Bouton mlli kaykoun Actif */
  .syndify-tab-active {
    @apply font-bold text-syndify-dark border-syndify-accent;
  }

  /* L-Bouton mlli kaykoun Inactif */
  .syndify-tab-inactive {
    @apply font-medium text-gray-500 border-transparent hover:text-syndify-dark;
  }

  /* ==========================================
     🟢 SYNDIFY GLOBAL DROPDOWNS (Menus)
     ========================================== */

  /* L-Cadre dyal l-Menu */
  .syndify-dropdown-menu {
    @apply absolute w-40 bg-white rounded-xl border border-gray-100 z-[999] shadow-[0_10px_25px_-5px_rgba(0,0,0,0.1)] overflow-hidden flex flex-col py-0 !mt-0 !mb-0;
  }
  
  /* Bouton 3adi wset l-Menu */
  .syndify-dropdown-item {
    @apply w-full flex items-center gap-3 px-4 py-3 text-[13px] transition-colors cursor-pointer border-0 bg-transparent outline-none hover:bg-slate-50;
  }

  /* Bouton 7mer (Supprimer/Danger) wset l-Menu */
  .syndify-dropdown-item-danger {
    @apply w-full flex items-center gap-3 px-4 py-3 text-[13px] transition-colors cursor-pointer border-0 bg-transparent outline-none hover:bg-red-50;
  }

  /* S-ster lli kay-fre9 bin l-boutonat */
  .syndify-dropdown-divider {
    @apply h-px bg-gray-100 w-full m-0;
  }
  
  /* L-Banda l-fou9aniya */
  .syndify-card-header {
    @apply p-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50;
  }

  /* L-3ounwan lli wset mnha */
  .syndify-card-title {
    @apply text-[15px] font-bold text-syndify-dark m-0;
  }
}



{
       "full_name": "nabil.qadri",
            "email": "nabil.qadri@spherebleue.com",
            "phone": "07000005", 
            "agreed_on_terms": true
}


$email = 'yassine.oc@gmail.com';



// 1. N-jibou l-user dyalk

$user = DB::table('users')->where('email', $email)->first();



if (!$user) {

    echo "⚠️ L-User ma-kaynsh! T2kkdi wesh derti Register f Postman/Angular 9bel.\n";

} else {

    // 2. N-jibou l-ID dyal l-Résidence li m-lsaqa m3ah f user_as_owner

    $link = DB::table('user_as_owner')->where('user_id', $user->id)->first();

    

    if (!$link) {

        echo "⚠️ Had l-user ma-3ndou 7ta résidence m-lsaqa m3ah!\n";

    } else {

        $propriete_id = $link->propriete_id ?? $link->sp_identifier;



        // 3. N-updatiw dik l-Résidence nishan b-l-m3loumat l-jdid li bghiti

        DB::table('proprietes')->where('id', $propriete_id)->update([

            'nom' => "Résidence elghizlan",

            'city' => "Casablanca",

            'address' => "Boulverad Test",

            'updated_at' => now()

        ]);



     


        echo "\n✅ BOOM! L-Résidence dyal [yassine.oc@gmail.com] رجعات hiya:\n";

        echo "🏠 Nom: Résidence Les Test\n";

        echo "📍 City: Casablanca\n";

        echo "🗺️ Address: Angle Ain Diab\n";

    }

}



  class="shrink-0 relative w-full pb-4 sm:pb-0"
    [parentPath]="residence.nom" 
    currentPath="Historique du compte"
    title="Historique du compte"
    [description]="residence.adresse">


App\Models\User::where('email', 'email.spherebleue@gmail.com')->delete();