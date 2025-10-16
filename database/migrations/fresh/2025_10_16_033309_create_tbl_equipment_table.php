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
        Schema::create('tbl_equipment', function (Blueprint $table) {
            $table->bigIncrements('equipment_id');
            $table->unsignedBigInteger('branch_id')->nullable()->index('branch_id');
            $table->string('equipment_name', 100)->nullable();
            $table->integer('equipment_quantity')->nullable();
            $table->text('equipment_description')->nullable();
            $table->string('equipment_image')->nullable();
            $table->string('equipment_category', 100)->nullable();
            $table->string('equipment_status', 50)->default('Available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_equipment');
    }
};
