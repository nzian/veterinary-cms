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
        Schema::table('tbl_boarding_record', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_boarding_record', 'serv_id')) {
                $table->unsignedBigInteger('serv_id')->nullable()->after('pet_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_boarding_record', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_boarding_record', 'serv_id')) {
                $table->dropColumn('serv_id');
            }
        });
    }
};
