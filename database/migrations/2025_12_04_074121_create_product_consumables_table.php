<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This table links a parent product (e.g., vaccine) to its required consumables (e.g., syringe).
     * When the parent product is used, all linked consumables will also be deducted from inventory.
     */
    public function up(): void
    {
        Schema::create('tbl_product_consumables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('The parent product (e.g., vaccine)');
            $table->unsignedBigInteger('consumable_product_id')->comment('The linked consumable (e.g., syringe)');
            $table->integer('quantity')->default(1)->comment('Quantity of consumable needed per use of parent product');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('product_id')->references('prod_id')->on('tbl_prod')->onDelete('cascade');
            $table->foreign('consumable_product_id')->references('prod_id')->on('tbl_prod')->onDelete('cascade');
            
            // Prevent duplicate links
            $table->unique(['product_id', 'consumable_product_id'], 'unique_product_consumable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_product_consumables');
    }
};
