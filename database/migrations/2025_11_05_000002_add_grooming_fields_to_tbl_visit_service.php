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
        if (Schema::hasTable('tbl_visit_service')) {
            Schema::table('tbl_visit_service', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_visit_service', 'coat_condition')) {
                    $table->string('coat_condition')->nullable()->after('serv_id');
                }
                if (!Schema::hasColumn('tbl_visit_service', 'skin_issues')) {
                    $table->json('skin_issues')->nullable()->after('coat_condition');
                }
                if (!Schema::hasColumn('tbl_visit_service', 'notes')) {
                    $table->text('notes')->nullable()->after('skin_issues');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tbl_visit_service')) {
            Schema::table('tbl_visit_service', function (Blueprint $table) {
                if (Schema::hasColumn('tbl_visit_service', 'notes')) {
                    $table->dropColumn('notes');
                }
                if (Schema::hasColumn('tbl_visit_service', 'skin_issues')) {
                    $table->dropColumn('skin_issues');
                }
                if (Schema::hasColumn('tbl_visit_service', 'coat_condition')) {
                    $table->dropColumn('coat_condition');
                }
            });
        }
    }
};