<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('user_owner_unit', function (Blueprint $table) {
            $table->id();
            
            // ✅ uuid بدل foreignId
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade'); 
            
            $table->integer('status')->default(1);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_owner_unit');
    }
};