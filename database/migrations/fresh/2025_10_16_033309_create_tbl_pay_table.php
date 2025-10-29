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
        Schema::create('tbl_pay', function (Blueprint $table) {
            $table->bigIncrements('pay_id');
            $table->double('pay_change')->nullable();
            $table->double('pay_cashAmount')->nullable();
            $table->double('pay_total')->nullable();
            $table->integer('bill_id')->nullable()->index('tbl_pay_bill_id_foreign');
            $table->integer('ord_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_pay');
    }
};
