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
        Schema::table('tbl_ord', function (Blueprint $table) {
            $table->foreign(['prod_id'])->references(['prod_id'])->on('tbl_prod')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['user_id'])->references(['user_id'])->on('tbl_user')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_ord', function (Blueprint $table) {
            $table->dropForeign('tbl_ord_prod_id_foreign');
            $table->dropForeign('tbl_ord_user_id_foreign');
        });
    }
};
