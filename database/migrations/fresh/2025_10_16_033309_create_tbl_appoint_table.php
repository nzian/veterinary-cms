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
        Schema::create('tbl_appoint', function (Blueprint $table) {
            $table->bigIncrements('appoint_id');
            $table->time('appoint_time')->nullable();
            $table->string('appoint_status', 50)->nullable();
            $table->integer('reschedule_count')->default(0);
            $table->date('original_date')->nullable();
            $table->date('appoint_date')->nullable();
            $table->timestamp('last_rescheduled_at')->nullable();
            $table->text('appoint_description')->nullable();
            $table->longText('change_history')->nullable();
            $table->string('appoint_type', 50)->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_appoint');
    }
};
