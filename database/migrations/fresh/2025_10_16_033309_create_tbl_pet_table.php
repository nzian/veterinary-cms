<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbl_pet', function (Blueprint $table) {
            $table->bigIncrements('pet_id');
            $table->double('pet_weight')->nullable();
            $table->string('pet_species', 50)->nullable();
            $table->string('pet_breed', 50)->nullable();
            $table->string('pet_age', 20)->nullable();
            $table->date('pet_birthdate')->nullable();
            $table->string('pet_name', 50)->nullable();
            $table->string('pet_photo')->nullable();
            $table->string('pet_gender', 10)->nullable();
            $table->date('pet_registration')->nullable();
            $table->double('pet_temperature')->nullable();
            $table->unsignedBigInteger('own_id')->nullable()->index('tbl_pet_own_id_foreign');
            $table->unsignedBigInteger('user_id')->index('fk_pet_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_pet');
    }
};
