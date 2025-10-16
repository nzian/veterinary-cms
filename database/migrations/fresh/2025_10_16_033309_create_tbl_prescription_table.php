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
        Schema::create('tbl_prescription', function (Blueprint $table) {
            $table->bigIncrements('prescription_id');
            $table->unsignedBigInteger('pet_id')->index('pet_id');
            $table->integer('branch_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->date('prescription_date');
            $table->text('notes')->nullable();
            $table->text('differential_diagnosis')->nullable();
            $table->text('medication');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_prescription');
    }
};
