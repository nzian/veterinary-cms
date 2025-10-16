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
        Schema::table('tbl_equipment', function (Blueprint $table) {
            $table->foreign(['branch_id'], 'tbl_equipment_ibfk_1')->references(['branch_id'])->on('tbl_branch')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_equipment', function (Blueprint $table) {
            $table->dropForeign('tbl_equipment_ibfk_1');
        });
    }
};
