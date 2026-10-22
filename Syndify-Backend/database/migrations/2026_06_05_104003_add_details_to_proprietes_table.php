<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('proprietes', function (Blueprint $table) {
            // 🟢 كنزيدو الشرط باش نتأكدو واش العمود ديجا كاين أو لا باش مانطيحوش فـ الإيرور
            if (!Schema::hasColumn('proprietes', 'siret')) {
                $table->string('siret')->nullable()->after('address');
            }
            if (!Schema::hasColumn('proprietes', 'ville')) {
                $table->string('ville')->nullable()->after('siret');
            }
            if (!Schema::hasColumn('proprietes', 'code_postal')) {
                $table->string('code_postal')->nullable()->after('ville');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('proprietes', function (Blueprint $table) {
            if (Schema::hasColumn('proprietes', 'siret')) {
                $table->dropColumn('siret');
            }
            if (Schema::hasColumn('proprietes', 'ville')) {
                $table->dropColumn('ville');
            }
            if (Schema::hasColumn('proprietes', 'code_postal')) {
                $table->dropColumn('code_postal');
            }
        });
    }
};