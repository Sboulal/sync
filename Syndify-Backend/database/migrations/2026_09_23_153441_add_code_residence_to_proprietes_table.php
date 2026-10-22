<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('proprietes', function (Blueprint $table) {
            // 🟢 On ajoute la colonne ici
            $table->string('code_residence')->nullable()->unique()->after('slug');
        });
    }

    public function down()
    {
        Schema::table('proprietes', function (Blueprint $table) {
            $table->dropColumn('code_residence');
        });
    }
};