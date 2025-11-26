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
        Schema::create('product_damage_pullout', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pd_prod_id');
            $table->unsignedBigInteger('stock_id');
            $table->integer('pullout_quantity')->default(0);
            $table->integer('damage_quantity')->default(0);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('pd_prod_id')
                  ->references('prod_id')
                  ->on('tbl_prod')
                  ->onDelete('cascade');

            $table->foreign('stock_id')
                  ->references('id')
                  ->on('product_stock')
                  ->onDelete('cascade');

            $table->foreign('created_by')
                  ->references('user_id')
                  ->on('tbl_user')
                  ->onDelete('set null');

            // Index for better query performance
            $table->index(['pd_prod_id', 'stock_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_damage_pullout');
    }
};
