<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tbl_boarding_record', function (Blueprint $table) {
            // Rename columns to match code and ensure all required columns exist
            if (Schema::hasColumn('tbl_boarding_record', 'checkin_date')) {
                $table->renameColumn('checkin_date', 'check_in_date');
            }
            if (Schema::hasColumn('tbl_boarding_record', 'checkout_date')) {
                $table->renameColumn('checkout_date', 'check_out_date');
            }
            if (!Schema::hasColumn('tbl_boarding_record', 'check_in_date')) {
                $table->dateTime('check_in_date')->nullable()->after('pet_id');
            }
            if (!Schema::hasColumn('tbl_boarding_record', 'check_out_date')) {
                $table->dateTime('check_out_date')->nullable()->after('check_in_date');
            }
            if (!Schema::hasColumn('tbl_boarding_record', 'room_no')) {
                $table->string('room_no', 100)->nullable()->after('check_out_date');
            }
            if (!Schema::hasColumn('tbl_boarding_record', 'feeding_schedule')) {
                $table->text('feeding_schedule')->nullable()->after('room_no');
            }
            if (!Schema::hasColumn('tbl_boarding_record', 'medications')) {
                $table->text('medications')->nullable()->after('feeding_schedule');
            }
            if (!Schema::hasColumn('tbl_boarding_record', 'daily_notes')) {
                $table->text('daily_notes')->nullable()->after('medications');
            }
            if (!Schema::hasColumn('tbl_boarding_record', 'status')) {
                $table->string('status', 50)->nullable()->after('daily_notes');
            }
            if (!Schema::hasColumn('tbl_boarding_record', 'handled_by')) {
                $table->string('handled_by', 100)->nullable()->after('status');
            }
            if (!Schema::hasColumn('tbl_boarding_record', 'total_days')) {
                $table->integer('total_days')->nullable()->after('handled_by');
            }
            if (!Schema::hasColumn('tbl_boarding_record', 'serv_id')) {
                $table->unsignedBigInteger('serv_id')->nullable()->after('total_days');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_boarding_record', function (Blueprint $table) {
            // Optionally, reverse renames (not always safe)
            if (Schema::hasColumn('tbl_boarding_record', 'check_in_date')) {
                $table->renameColumn('check_in_date', 'checkin_date');
            }
            if (Schema::hasColumn('tbl_boarding_record', 'check_out_date')) {
                $table->renameColumn('check_out_date', 'checkout_date');
            }
        });
    }
};
