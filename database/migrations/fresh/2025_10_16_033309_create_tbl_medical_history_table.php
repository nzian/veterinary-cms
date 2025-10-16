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
        Schema::create('tbl_medical_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pet_id')->index();
            $table->decimal('weight')->nullable();
            $table->decimal('temperature', 5)->nullable();
            $table->date('visit_date')->index();
            $table->text('diagnosis');
            $table->text('treatment');
            $table->string('medication', 300)->nullable();
            $table->string('veterinarian_name', 100);
            $table->date('follow_up_date')->nullable();
            $table->text('notes')->nullable();
            $table->bigInteger('user_id')->nullable()->index('fk_medical_user');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_medical_history');
    }
};
