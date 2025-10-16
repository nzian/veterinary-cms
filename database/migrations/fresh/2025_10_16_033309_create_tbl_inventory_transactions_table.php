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
        Schema::create('tbl_inventory_transactions', function (Blueprint $table) {
            $table->comment('Tracks all inventory movements for audit trail');
            $table->bigIncrements('transaction_id');
            $table->unsignedBigInteger('prod_id')->index('idx_prod_id');
            $table->unsignedBigInteger('appoint_id')->nullable()->index('idx_appoint_id');
            $table->unsignedBigInteger('serv_id')->nullable()->index('idx_serv_id');
            $table->decimal('quantity_change', 10)->comment('Positive for additions, negative for deductions');
            $table->enum('transaction_type', ['purchase', 'service_usage', 'adjustment', 'return', 'waste', 'damage', 'pullout'])->default('adjustment')->index('idx_transaction_type');
            $table->string('reference')->nullable()->comment('Reference number or description');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable()->index('idx_performed_by');
            $table->timestamp('created_at')->nullable()->useCurrent()->index('idx_created_at');
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_inventory_transactions');
    }
};
