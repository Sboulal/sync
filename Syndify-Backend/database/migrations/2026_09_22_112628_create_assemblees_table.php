<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('assemblees', function (Blueprint $table) {
            $table->id();
            $table->string('propriete_id')->nullable(); 
            
            // 🟢 HNA TBDL L'TYPE: Reddinaha string(36) hit l'id dyal l'user 3ndk UUID (Text)
            $table->string('created_by', 36); 
            
            $table->string('titre');
            $table->date('date_ag');
            $table->string('heure_ag', 10);
            $table->string('lieu');
            $table->text('ordre_jour');
            
            $table->enum('statut', ['planifiee', 'terminee'])->default('planifiee');
            $table->string('pv_url')->nullable(); 
            $table->boolean('is_notified')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('assemblees');
    }
};