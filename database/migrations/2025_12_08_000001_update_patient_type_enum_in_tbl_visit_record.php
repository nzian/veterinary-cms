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
        DB::statement("ALTER TABLE tbl_visit_record MODIFY COLUMN patient_type ENUM('inpatient', 'outpatient', 'emergency') NOT NULL DEFAULT 'outpatient'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE tbl_visit_record MODIFY COLUMN patient_type ENUM('admission', 'outpatient', 'boarding') NOT NULL DEFAULT 'outpatient'");
    }
};
