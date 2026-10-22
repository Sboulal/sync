<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('user_as_owner', function (Blueprint $table) {
            $table->id();

            // ✅ uuid فقط — بلا default لأن القيمة كتجي من users.id
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->string('propriete_id'); 
            $table->foreign('propriete_id')->references('id')->on('proprietes')->onDelete('cascade');
            
            $table->integer('status')->default(1); 
            $table->double('balance_prev', 15, 2)->default(0); 
            $table->double('balance_new', 15, 2)->default(0);  
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_as_owner');
    }
};