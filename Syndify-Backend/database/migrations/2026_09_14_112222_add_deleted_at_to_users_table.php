<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kan-t2kdo wach la colonne makaynach 3ad kanzidouha
        if (!Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes(); 
            });
        }
    }

    public function down(): void
    {
        // Kan-t2kdo wach la colonne kayna 3ad kanms7ouha bach manty7ouch f l'erreur
        if (Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};