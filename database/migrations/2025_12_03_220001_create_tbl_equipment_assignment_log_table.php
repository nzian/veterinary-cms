<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tracks equipment assignments/releases for boarding, visits, etc.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tbl_equipment_assignment_log')) {
            Schema::create('tbl_equipment_assignment_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('equipment_id');
                $table->string('action_type', 50); // assigned, released, maintenance, status_change
                $table->unsignedBigInteger('visit_id')->nullable();
                $table->unsignedBigInteger('pet_id')->nullable();
                $table->unsignedBigInteger('performed_by')->nullable();
                $table->integer('quantity_changed')->default(1);
                $table->string('previous_status', 50)->nullable();
                $table->string('new_status', 50)->nullable();
                $table->integer('previous_available')->nullable();
                $table->integer('new_available')->nullable();
                $table->string('reference', 100)->nullable(); // e.g., "Boarding #123"
                $table->text('notes')->nullable();
                $table->timestamps();
                
                $table->foreign('equipment_id')
                      ->references('equipment_id')
                      ->on('tbl_equipment')
                      ->onDelete('cascade');
                
                $table->index(['equipment_id', 'created_at']);
                $table->index(['action_type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_equipment_assignment_log');
    }
};
