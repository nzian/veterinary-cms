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
        Schema::table('tbl_appoint_serv', function (Blueprint $table) {
            $table->foreign(['prod_id'], 'fk_appoint_serv_prod')->references(['prod_id'])->on('tbl_prod')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['vet_user_id'], 'fk_tbl_appoint_serv_vet_user_id')->references(['user_id'])->on('tbl_user')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['appoint_id'])->references(['appoint_id'])->on('tbl_appoint')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['serv_id'])->references(['serv_id'])->on('tbl_serv')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_appoint_serv', function (Blueprint $table) {
            $table->dropForeign('fk_appoint_serv_prod');
            $table->dropForeign('fk_tbl_appoint_serv_vet_user_id');
            $table->dropForeign('tbl_appoint_serv_appoint_id_foreign');
            $table->dropForeign('tbl_appoint_serv_serv_id_foreign');
        });
    }
};
