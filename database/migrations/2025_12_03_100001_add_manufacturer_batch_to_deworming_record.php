<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add manufacturer and batch_no fields to tbl_deworming_record
     * Similar to vaccination record for tracking product details
     */
    public function up(): void
    {
        if (Schema::hasTable('tbl_deworming_record')) {
            Schema::table('tbl_deworming_record', function (Blueprint $table) {
                // Add manufacturer column if not exists
                if (!Schema::hasColumn('tbl_deworming_record', 'manufacturer')) {
                    $table->string('manufacturer', 255)->nullable()->after('dosage');
                }
                
                // Add batch_no column if not exists
                if (!Schema::hasColumn('tbl_deworming_record', 'batch_no')) {
                    $table->string('batch_no', 100)->nullable()->after('manufacturer');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tbl_deworming_record')) {
            Schema::table('tbl_deworming_record', function (Blueprint $table) {
                // Remove manufacturer column if exists
                if (Schema::hasColumn('tbl_deworming_record', 'manufacturer')) {
                    $table->dropColumn('manufacturer');
                }
                
                // Remove batch_no column if exists
                if (Schema::hasColumn('tbl_deworming_record', 'batch_no')) {
                    $table->dropColumn('batch_no');
                }
            });
        }
    }
};
