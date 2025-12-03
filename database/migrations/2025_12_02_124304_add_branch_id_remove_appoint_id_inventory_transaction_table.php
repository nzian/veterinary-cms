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
        //
        Schema::disableForeignKeyConstraints();
        if(Schema::hasColumn('tbl_inventory_transactions', 'appoint_id') && !Schema::hasColumn('tbl_inventory_transactions', 'batch_id')) {
            Schema::table('tbl_inventory_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('batch_id')->after('transaction_type');
                $table->foreign('batch_id')->references('id')->on('tbl_product_stock')->onDelete('cascade');
                $table->dropForeign(['appoint_id']);
                $table->dropColumn('appoint_id');
            });
        }
        if(!Schema::hasColumn('tbl_inventory_transactions', 'visit_id')) {
            Schema::table('tbl_inventory_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('visit_id')->after('appoint_id')->nullable();
                $table->foreign('visit_id')->references('visit_id')->on('tbl_visit')->onDelete('cascade');
            });
        }
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        if(Schema::hasColumn('tbl_inventory_transactions', 'batch_id') && !Schema::hasColumn('tbl_inventory_transactions', 'appoint_id')) {
        Schema::table('tbl_inventory_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('appoint_id')->after('transaction_type');
            $table->foreign('appoint_id')->references('appoint_id')->on('tbl_appointments')->onDelete('cascade');
            $table->dropForeign(['batch_id']);
            $table->dropColumn('batch_id');
        });
    }
        if(Schema::hasColumn('tbl_inventory_transactions', 'visit_id')) {
            Schema::table('tbl_inventory_transactions', function (Blueprint $table) {
                $table->dropForeign(['visit_id']);
                $table->dropColumn('visit_id');
            });
        
        }
        Schema::enableForeignKeyConstraints();
    }
};
