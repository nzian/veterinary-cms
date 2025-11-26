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
        Schema::table('tbl_service_products', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_service_products', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('is_billable');
                $table->foreign('created_by')->references('user_id')->on('tbl_user')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_service_products', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
