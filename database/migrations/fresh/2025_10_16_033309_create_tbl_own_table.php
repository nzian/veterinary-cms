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
        Schema::create('tbl_own', function (Blueprint $table) {
            $table->bigIncrements('own_id');
            $table->string('own_location', 100)->nullable();
            $table->unsignedBigInteger('user_id')->index('fk_own_user');
            $table->string('own_contactnum', 20)->nullable();
            $table->string('own_name', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_own');
    }
};
