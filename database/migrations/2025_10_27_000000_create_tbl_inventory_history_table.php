<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_inventory_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('prod_id')->nullable();
            $table->string('type', 50)->nullable(); // e.g. 'in', 'out', 'adjustment'
            $table->integer('quantity')->default(0);
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_inventory_history');
    }
};
