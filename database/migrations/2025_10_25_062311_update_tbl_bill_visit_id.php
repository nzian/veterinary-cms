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
        Schema::table('tbl_bill', function (Blueprint $table) {
            // Add visit_id column
            $table->unsignedBigInteger('visit_id')->nullable()->after('ord_id');
            
            // Add foreign key constraint
            $table->foreign('visit_id')
                  ->references('visit_id')
                  ->on('tbl_visit_record')
                  ->onDelete('set null');
                  
            // Drop the appoint_id column if it exists
            if (Schema::hasColumn('tbl_bill', 'appoint_id')) {
                $table->dropForeign(['appoint_id']);
                $table->dropColumn('appoint_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_bill', function (Blueprint $table) {
            // Drop the foreign key and column
            $table->dropForeign(['visit_id']);
            $table->dropColumn('visit_id');
            
            // Add back appoint_id if needed
            if (!Schema::hasColumn('tbl_bill', 'appoint_id')) {
                $table->unsignedBigInteger('appoint_id')->nullable()->after('ord_id');
                $table->foreign('appoint_id')
                      ->references('appoint_id')
                      ->on('tbl_appointment')
                      ->onDelete('set null');
            }
        });
    }
};
