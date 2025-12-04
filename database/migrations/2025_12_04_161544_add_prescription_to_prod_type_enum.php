<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the enum to include Prescription
        DB::statement("ALTER TABLE tbl_prod MODIFY COLUMN prod_type ENUM('Sale', 'Consumable', 'Prescription') DEFAULT 'Sale'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum (first update any Prescription to Sale)
        DB::statement("UPDATE tbl_prod SET prod_type = 'Sale' WHERE prod_type = 'Prescription'");
        DB::statement("ALTER TABLE tbl_prod MODIFY COLUMN prod_type ENUM('Sale', 'Consumable') DEFAULT 'Sale'");
    }
};
