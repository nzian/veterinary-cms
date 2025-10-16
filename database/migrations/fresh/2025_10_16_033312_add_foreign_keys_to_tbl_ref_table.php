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
        Schema::table('tbl_ref', function (Blueprint $table) {
            $table->foreign(['appoint_id'])->references(['appoint_id'])->on('tbl_appoint')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_ref', function (Blueprint $table) {
            $table->dropForeign('tbl_ref_appoint_id_foreign');
        });
    }
};
