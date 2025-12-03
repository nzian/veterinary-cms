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
        Schema::table('tbl_equipment', function (Blueprint $table) {
            $table->integer('equipment_available')->default(0)->after('equipment_quantity');
            $table->integer('equipment_maintenance')->default(0)->after('equipment_available');
            $table->integer('equipment_out_of_service')->default(0)->after('equipment_maintenance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_equipment', function (Blueprint $table) {
            $table->dropColumn(['equipment_available', 'equipment_maintenance', 'equipment_out_of_service']);
        });
    }
};
