<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{public function up(): void
{
    DB::statement('ALTER TABLE depense_for_owner DROP CONSTRAINT IF EXISTS depense_for_owner_user_id_foreign');
    DB::statement('ALTER TABLE depense_for_owner ALTER COLUMN user_id TYPE VARCHAR(36) USING user_id::VARCHAR');
}

public function down(): void
{
    DB::statement('ALTER TABLE depense_for_owner ALTER COLUMN user_id TYPE BIGINT USING user_id::BIGINT');
}
};
