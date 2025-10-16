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
        Schema::table('tbl_bill', function (Blueprint $table) {
            $table->foreign(['ord_id'])->references(['ord_id'])->on('tbl_ord')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_bill', function (Blueprint $table) {
            $table->dropForeign('tbl_bill_ord_id_foreign');
        });
    }
};
