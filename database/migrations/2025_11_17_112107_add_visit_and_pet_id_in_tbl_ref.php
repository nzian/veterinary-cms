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
            //
            $table->unsignedBigInteger('visit_id')->after('ref_id');
            $table->unsignedBigInteger('pet_id')->after('visit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_ref', function (Blueprint $table) {
            //
            $table->dropColumn('visit_id');
            $table->dropColumn('pet_id');
        });
    }
};
