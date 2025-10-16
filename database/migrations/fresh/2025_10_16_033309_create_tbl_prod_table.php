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
        Schema::create('tbl_prod', function (Blueprint $table) {
            $table->bigIncrements('prod_id');
            $table->integer('prod_reorderlevel')->nullable();
            $table->string('prod_image')->nullable();
            $table->integer('prod_damaged')->nullable()->default(0);
            $table->integer('prod_pullout')->nullable()->default(0);
            $table->date('prod_expiry')->nullable();
            $table->integer('prod_stocks')->nullable();
            $table->integer('prod_min_stock')->nullable()->default(10);
            $table->double('prod_price')->nullable();
            $table->string('prod_category', 50)->nullable();
            $table->text('prod_description')->nullable();
            $table->string('prod_name', 200)->nullable();
            $table->unsignedInteger('branch_id')->nullable();
            $table->unsignedBigInteger('ord_id')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_prod');
    }
};
