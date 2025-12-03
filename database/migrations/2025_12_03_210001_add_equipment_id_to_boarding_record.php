<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add equipment_id to boarding record to track assigned cage/room
     */
    public function up(): void
    {
        Schema::table('tbl_boarding_record', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_boarding_record', 'equipment_id')) {
                $table->unsignedBigInteger('equipment_id')->nullable()->after('room_no');
                
                $table->foreign('equipment_id')
                      ->references('equipment_id')
                      ->on('tbl_equipment')
                      ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_boarding_record', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_boarding_record', 'equipment_id')) {
                $table->dropForeign(['equipment_id']);
                $table->dropColumn('equipment_id');
            }
        });
    }
};
