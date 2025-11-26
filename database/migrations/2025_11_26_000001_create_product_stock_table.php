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
        Schema::create('product_stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_prod_id');
            $table->string('batch', 100)->nullable();
            $table->integer('quantity')->default(0);
            $table->date('expire_date');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('stock_prod_id')
                  ->references('prod_id')
                  ->on('tbl_prod')
                  ->onDelete('cascade');

            $table->foreign('created_by')
                  ->references('user_id')
                  ->on('tbl_user')
                  ->onDelete('set null');

            // Index for better query performance
            $table->index(['stock_prod_id', 'expire_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stock');
    }
};
