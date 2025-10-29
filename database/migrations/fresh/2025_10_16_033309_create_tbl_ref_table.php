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
        Schema::create('tbl_ref', function (Blueprint $table) {
            $table->bigIncrements('ref_id');
            $table->date('ref_date')->nullable();
            $table->text('ref_description')->nullable();
            $table->unsignedBigInteger('appoint_id')->nullable()->index('tbl_ref_appoint_id_foreign');
            $table->text('medical_history')->nullable();
            $table->text('tests_conducted')->nullable();
            $table->text('medications_given')->nullable();
            $table->unsignedInteger('ref_by')->nullable()->index('idx_ref_by');
            $table->integer('ref_to')->nullable()->index('idx_ref_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_ref');
    }
};
