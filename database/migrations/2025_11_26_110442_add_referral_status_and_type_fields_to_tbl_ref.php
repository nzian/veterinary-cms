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
            $table->enum('ref_status', ['pending', 'attended'])->default('pending')->after('ref_description');
            $table->enum('ref_type', ['interbranch', 'external'])->after('ref_status');
            $table->unsignedBigInteger('ref_company_id')->nullable()->after('ref_type');
            $table->unsignedBigInteger('referred_visit_id')->nullable()->after('ref_company_id');
            
            $table->foreign('ref_company_id')->references('id')->on('tbl_referral_companies')->onDelete('set null');
            $table->foreign('referred_visit_id')->references('visit_id')->on('tbl_visit_record')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_ref', function (Blueprint $table) {
            $table->dropForeign(['ref_company_id']);
            $table->dropForeign(['referred_visit_id']);
            $table->dropColumn(['ref_status', 'ref_type', 'ref_company_id', 'referred_visit_id']);
        });
    }
};
