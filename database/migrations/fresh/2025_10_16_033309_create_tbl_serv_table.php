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
        Schema::create('tbl_serv', function (Blueprint $table) {
            $table->bigIncrements('serv_id');
            $table->decimal('serv_price', 10)->nullable();
            $table->text('serv_description')->nullable();
            $table->string('serv_type', 50)->nullable();
            $table->string('serv_name', 100)->nullable();
            $table->integer('branch_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_serv');
    }
};
