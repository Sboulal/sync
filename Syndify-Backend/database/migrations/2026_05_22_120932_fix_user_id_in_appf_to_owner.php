<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ Supprime la foreign key si elle existe, puis change le type
        DB::statement('ALTER TABLE appf_to_owner DROP CONSTRAINT IF EXISTS appf_to_owner_user_id_foreign');
        DB::statement('ALTER TABLE appf_to_owner ALTER COLUMN user_id TYPE VARCHAR(36) USING user_id::VARCHAR');
    }

    public function down(): void
    {
        // 1. Nssuprimiw l'colonne li fiha l'UUID
        Schema::table('appf_to_owner', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        // 2. N3awdou n-creeyiwha b type BigInt
        Schema::table('appf_to_owner', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable();
        });
    }
};