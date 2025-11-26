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
        // Add service_id to surgical records
        if (Schema::hasTable('tbl_surgical_record')) {
            Schema::table('tbl_surgical_record', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_surgical_record', 'service_id')) {
                    $table->unsignedBigInteger('service_id')->nullable()->after('procedure_name');
                    $table->foreign('service_id')->references('serv_id')->on('tbl_serv')->onDelete('set null');
                }
            });
        }

        // Add service_id to diagnostic records
        if (Schema::hasTable('tbl_diagnostic_record')) {
            Schema::table('tbl_diagnostic_record', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_diagnostic_record', 'service_id')) {
                    $table->unsignedBigInteger('service_id')->nullable()->after('test_type');
                    $table->foreign('service_id')->references('serv_id')->on('tbl_serv')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove service_id from surgical records
        if (Schema::hasTable('tbl_surgical_record')) {
            Schema::table('tbl_surgical_record', function (Blueprint $table) {
                if (Schema::hasColumn('tbl_surgical_record', 'service_id')) {
                    $table->dropForeign(['service_id']);
                    $table->dropColumn('service_id');
                }
            });
        }

        // Remove service_id from diagnostic records
        if (Schema::hasTable('tbl_diagnostic_record')) {
            Schema::table('tbl_diagnostic_record', function (Blueprint $table) {
                if (Schema::hasColumn('tbl_diagnostic_record', 'service_id')) {
                    $table->dropForeign(['service_id']);
                    $table->dropColumn('service_id');
                }
            });
        }
    }
};
