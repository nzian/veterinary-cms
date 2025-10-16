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
        Schema::create('tbl_appoint_serv', function (Blueprint $table) {
            $table->bigIncrements('appoint_serv_id');
            $table->unsignedBigInteger('appoint_id')->nullable()->index('tbl_appoint_serv_appoint_id_foreign');
            $table->unsignedBigInteger('serv_id')->nullable()->index('tbl_appoint_serv_serv_id_foreign');
            $table->unsignedBigInteger('prod_id')->nullable()->index('fk_appoint_serv_prod');
            $table->unsignedBigInteger('vet_user_id')->nullable()->index('fk_tbl_appoint_serv_vet_user_id');
            $table->date('vacc_next_dose')->nullable();
            $table->string('vacc_batch_no')->nullable();
            $table->text('vacc_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_appoint_serv');
    }
};
