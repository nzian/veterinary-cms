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
            // Add fields for grouped billing by owner
            if (!Schema::hasColumn('tbl_bill', 'billing_group_id')) {
                $table->string('billing_group_id', 50)->nullable()->after('visit_id')->index();
            }
            if (!Schema::hasColumn('tbl_bill', 'owner_id')) {
                $table->unsignedBigInteger('owner_id')->nullable()->after('billing_group_id')->index();
            }
            if (!Schema::hasColumn('tbl_bill', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->default(0)->after('bill_status');
            }
            if (!Schema::hasColumn('tbl_bill', 'paid_amount')) {
                $table->decimal('paid_amount', 10, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('tbl_bill', 'is_group_parent')) {
                $table->boolean('is_group_parent')->default(false)->after('paid_amount')->comment('True if this is the main billing for payment');
            }
            
            // Add foreign key for owner - skip for now as we need to add it separately after column creation
            // The foreign key will be manually added if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_bill', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_bill', 'billing_group_id')) {
                $table->dropColumn('billing_group_id');
            }
            if (Schema::hasColumn('tbl_bill', 'owner_id')) {
                $table->dropColumn('owner_id');
            }
            if (Schema::hasColumn('tbl_bill', 'is_group_parent')) {
                $table->dropColumn('is_group_parent');
            }
            // Don't drop total_amount and paid_amount if they already existed
        });
    }
};
