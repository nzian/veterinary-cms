<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add visit_source field to track how the visit was created.
     * Values: 'walk-in' (default), 'referral', 'appointment'
     */
    public function up(): void
    {
        if (Schema::hasTable('tbl_visit_record')) {
            Schema::table('tbl_visit_record', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_visit_record', 'visit_source')) {
                    $table->string('visit_source', 50)->default('walk-in')->after('visit_status')
                        ->comment('Source of visit creation: walk-in, referral, appointment');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tbl_visit_record')) {
            Schema::table('tbl_visit_record', function (Blueprint $table) {
                if (Schema::hasColumn('tbl_visit_record', 'visit_source')) {
                    $table->dropColumn('visit_source');
                }
            });
        }
    }
};
