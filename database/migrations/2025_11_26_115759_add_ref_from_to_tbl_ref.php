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
            $table->unsignedBigInteger('ref_from')->nullable()->after('ref_by')->comment('Branch ID where referral was created');
            $table->foreign('ref_from')->references('branch_id')->on('tbl_branch')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_ref', function (Blueprint $table) {
            $table->dropForeign(['ref_from']);
            $table->dropColumn('ref_from');
        });
    }
};
