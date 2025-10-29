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
        Schema::create('tbl_visit_service', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('visit_id');
            $table->unsignedBigInteger('serv_id');
            $table->timestamps();

            $table->foreign('visit_id')->references('visit_id')->on('tbl_visit_record')->onDelete('cascade');
            $table->foreign('serv_id')->references('serv_id')->on('tbl_serv')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_visit_service');
    }
};
