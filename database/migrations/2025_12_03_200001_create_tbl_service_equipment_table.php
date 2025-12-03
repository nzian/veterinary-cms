<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Links equipment to boarding services
     */
    public function up(): void
    {
        Schema::create('tbl_service_equipment', function (Blueprint $table) {
            $table->comment('Links equipment to services (primarily for boarding)');
            $table->bigIncrements('id');
            $table->unsignedBigInteger('serv_id')->index('idx_se_serv_id');
            $table->unsignedBigInteger('equipment_id')->index('idx_se_equipment_id');
            $table->integer('quantity_used')->default(1)->comment('Number of equipment units used');
            $table->text('notes')->nullable()->comment('Additional notes about equipment usage');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['serv_id', 'equipment_id'], 'unique_service_equipment');
            
            // Foreign keys
            $table->foreign('serv_id')
                  ->references('serv_id')
                  ->on('tbl_serv')
                  ->onDelete('cascade');
                  
            $table->foreign('equipment_id')
                  ->references('equipment_id')
                  ->on('tbl_equipment')
                  ->onDelete('cascade');
                  
            $table->foreign('created_by')
                  ->references('user_id')
                  ->on('tbl_user')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_service_equipment');
    }
};
