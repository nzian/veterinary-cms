<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // First, modify the status column to include 'partial' as a valid value
        DB::statement("ALTER TABLE tbl_pay MODIFY COLUMN status ENUM('pending', 'paid', 'partial') NOT NULL DEFAULT 'pending'");
    }

    public function down()
    {
        // Revert back to original ENUM values if needed
        DB::statement("ALTER TABLE tbl_pay MODIFY COLUMN status ENUM('pending', 'paid') NOT NULL DEFAULT 'pending'");
    }
};
