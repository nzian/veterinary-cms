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
        Schema::create('tbl_bill', function (Blueprint $table) {
            $table->increments('bill_id');
            $table->date('bill_date')->nullable();
            $table->unsignedBigInteger('ord_id')->nullable()->index('tbl_bill_ord_id_foreign');
            $table->unsignedBigInteger('appoint_id')->nullable();
            $table->string('bill_status', 20)->default('Pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_bill');
    }
};
