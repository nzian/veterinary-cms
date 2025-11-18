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
                if (!Schema::hasColumn('tbl_visit_service', 'quantity')) {
                    $table->integer('quantity')->default(1)->after('serv_id');
                }
                if (!Schema::hasColumn('tbl_visit_service', 'unit_price')) {
                    $table->decimal('unit_price', 10, 2)->default(0)->after('quantity');
                }
                if (!Schema::hasColumn('tbl_visit_service', 'total_price')) {
                    $table->decimal('total_price', 10, 2)->default(0)->after('unit_price');
                }
                if (!Schema::hasColumn('tbl_visit_service', 'status')) {
                    $table->string('status')->default('pending')->after('total_price');
                }
                if (!Schema::hasColumn('tbl_visit_service', 'completed_at')) {
                    $table->timestamp('completed_at')->nullable()->after('status');
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
                if (Schema::hasColumn('tbl_visit_service', 'completed_at')) {
                    $table->dropColumn('completed_at');
                }
                if (Schema::hasColumn('tbl_visit_service', 'status')) {
                    $table->dropColumn('status');
                }
                if (Schema::hasColumn('tbl_visit_service', 'total_price')) {
                    $table->dropColumn('total_price');
                }
                if (Schema::hasColumn('tbl_visit_service', 'unit_price')) {
                    $table->dropColumn('unit_price');
                }
                if (Schema::hasColumn('tbl_visit_service', 'quantity')) {
                    $table->dropColumn('quantity');
                }
            });
        }
    }
};
