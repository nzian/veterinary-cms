<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add inpatient and emergency visit fields to tbl_visit_record
     * - cage_ward_number: For inpatient visits to track cage/ward assignment
     * - admission_notes: Notes for inpatient admission
     * - is_priority: Flag for emergency/high priority visits
     * - admission_date: Date when patient was admitted (for inpatient)
     * - discharge_date: Date when patient was discharged
     */
    public function up(): void
    {
        if (Schema::hasTable('tbl_visit_record')) {
            Schema::table('tbl_visit_record', function (Blueprint $table) {
                // Add cage/ward number for inpatient visits
                if (!Schema::hasColumn('tbl_visit_record', 'cage_ward_number')) {
                    $table->string('cage_ward_number', 50)->nullable()->after('patient_type');
                }
                
                // Add admission notes for inpatient visits
                if (!Schema::hasColumn('tbl_visit_record', 'admission_notes')) {
                    $table->text('admission_notes')->nullable()->after('cage_ward_number');
                }
                
                // Add priority flag for emergency visits
                if (!Schema::hasColumn('tbl_visit_record', 'is_priority')) {
                    $table->boolean('is_priority')->default(false)->after('admission_notes');
                }
                
                // Add admission date for inpatient tracking
                if (!Schema::hasColumn('tbl_visit_record', 'admission_date')) {
                    $table->datetime('admission_date')->nullable()->after('is_priority');
                }
                
                // Add discharge date for inpatient tracking
                if (!Schema::hasColumn('tbl_visit_record', 'discharge_date')) {
                    $table->datetime('discharge_date')->nullable()->after('admission_date');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tbl_visit_record')) {
            Schema::table('tbl_visit_record', function (Blueprint $table) {
                $columns = ['cage_ward_number', 'admission_notes', 'is_priority', 'admission_date', 'discharge_date'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('tbl_visit_record', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
