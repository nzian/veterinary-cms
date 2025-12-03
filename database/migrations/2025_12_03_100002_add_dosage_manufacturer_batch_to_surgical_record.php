<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add dosage, manufacturer and batch_no fields to tbl_surgical_record
     * Similar to vaccination record for tracking anesthesia product details
     */
    public function up(): void
    {
        if (Schema::hasTable('tbl_surgical_record')) {
            Schema::table('tbl_surgical_record', function (Blueprint $table) {
                // Add dosage column if not exists
                if (!Schema::hasColumn('tbl_surgical_record', 'dosage')) {
                    $table->string('dosage', 100)->nullable()->after('anesthesia_used');
                }
                
                // Add manufacturer column if not exists
                if (!Schema::hasColumn('tbl_surgical_record', 'manufacturer')) {
                    $table->string('manufacturer', 255)->nullable()->after('dosage');
                }
                
                // Add batch_no column if not exists
                if (!Schema::hasColumn('tbl_surgical_record', 'batch_no')) {
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
        if (Schema::hasTable('tbl_surgical_record')) {
            Schema::table('tbl_surgical_record', function (Blueprint $table) {
                // Remove dosage column if exists
                if (Schema::hasColumn('tbl_surgical_record', 'dosage')) {
                    $table->dropColumn('dosage');
                }
                
                // Remove manufacturer column if exists
                if (Schema::hasColumn('tbl_surgical_record', 'manufacturer')) {
                    $table->dropColumn('manufacturer');
                }
                
                // Remove batch_no column if exists
                if (Schema::hasColumn('tbl_surgical_record', 'batch_no')) {
                    $table->dropColumn('batch_no');
                }
            });
        }
    }
};
