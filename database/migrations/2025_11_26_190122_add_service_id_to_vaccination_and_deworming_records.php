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
        // Add service_id to vaccination records
        if (Schema::hasTable('tbl_vaccination_record')) {
            Schema::table('tbl_vaccination_record', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_vaccination_record', 'service_id')) {
                    $table->unsignedBigInteger('service_id')->nullable()->after('vaccine_name');
                    $table->foreign('service_id')->references('serv_id')->on('tbl_serv')->onDelete('set null');
                }
            });
        }

        // Add service_id to deworming records
        if (Schema::hasTable('tbl_deworming_record')) {
            Schema::table('tbl_deworming_record', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_deworming_record', 'service_id')) {
                    $table->unsignedBigInteger('service_id')->nullable()->after('dewormer_name');
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
        // Remove service_id from vaccination records
        if (Schema::hasTable('tbl_vaccination_record')) {
            Schema::table('tbl_vaccination_record', function (Blueprint $table) {
                if (Schema::hasColumn('tbl_vaccination_record', 'service_id')) {
                    $table->dropForeign(['service_id']);
                    $table->dropColumn('service_id');
                }
            });
        }

        // Remove service_id from deworming records
        if (Schema::hasTable('tbl_deworming_record')) {
            Schema::table('tbl_deworming_record', function (Blueprint $table) {
                if (Schema::hasColumn('tbl_deworming_record', 'service_id')) {
                    $table->dropForeign(['service_id']);
                    $table->dropColumn('service_id');
                }
            });
        }
    }
};
