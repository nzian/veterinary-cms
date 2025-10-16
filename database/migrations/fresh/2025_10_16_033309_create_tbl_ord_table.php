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
        Schema::create('tbl_ord', function (Blueprint $table) {
            $table->bigIncrements('ord_id');
            $table->string('transaction_id', 50)->nullable();
            $table->integer('ord_quantity')->nullable();
            $table->double('ord_total')->nullable();
            $table->date('ord_date')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index('tbl_ord_user_id_foreign');
            $table->unsignedBigInteger('prod_id')->nullable()->index('tbl_ord_prod_id_foreign');
            $table->unsignedBigInteger('bill_id')->nullable()->index('idx_bill_id');
            $table->integer('own_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_ord');
    }
};
