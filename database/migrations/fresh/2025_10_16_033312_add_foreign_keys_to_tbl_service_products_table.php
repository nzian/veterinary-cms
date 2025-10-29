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
        Schema::table('tbl_service_products', function (Blueprint $table) {
            $table->foreign(['prod_id'], 'fk_service_products_prod')->references(['prod_id'])->on('tbl_prod')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['serv_id'], 'fk_service_products_serv')->references(['serv_id'])->on('tbl_serv')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_service_products', function (Blueprint $table) {
            $table->dropForeign('fk_service_products_prod');
            $table->dropForeign('fk_service_products_serv');
        });
    }
};
