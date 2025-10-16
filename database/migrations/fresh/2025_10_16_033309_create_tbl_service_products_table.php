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
        Schema::create('tbl_service_products', function (Blueprint $table) {
            $table->comment('Links products/medicines to services');
            $table->bigIncrements('id');
            $table->unsignedBigInteger('serv_id')->index('idx_serv_id');
            $table->unsignedBigInteger('prod_id')->index('idx_prod_id');
            $table->decimal('quantity_used', 10)->default(1);
            $table->boolean('is_billable')->default(false)->comment('0 = included in service, 1 = billable separately');
            $table->timestamps();

            $table->unique(['serv_id', 'prod_id'], 'unique_service_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_service_products');
    }
};
