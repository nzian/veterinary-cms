<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tbl_visit_record', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_visit_record', 'grooming_status')) {
                $table->string('grooming_status', 50)->nullable()->default('Waiting')->after('patient_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_visit_record', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_visit_record', 'grooming_status')) {
                $table->dropColumn('grooming_status');
            }
        });
    }
};
