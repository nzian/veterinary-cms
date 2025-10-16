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
        Schema::table('tbl_inventory_transactions', function (Blueprint $table) {
            $table->foreign(['appoint_id'], 'fk_inventory_trans_appoint')->references(['appoint_id'])->on('tbl_appoint')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['prod_id'], 'fk_inventory_trans_prod')->references(['prod_id'])->on('tbl_prod')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['serv_id'], 'fk_inventory_trans_serv')->references(['serv_id'])->on('tbl_serv')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['performed_by'], 'fk_inventory_trans_user')->references(['user_id'])->on('tbl_user')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_inventory_transactions', function (Blueprint $table) {
            $table->dropForeign('fk_inventory_trans_appoint');
            $table->dropForeign('fk_inventory_trans_prod');
            $table->dropForeign('fk_inventory_trans_serv');
            $table->dropForeign('fk_inventory_trans_user');
        });
    }
};
