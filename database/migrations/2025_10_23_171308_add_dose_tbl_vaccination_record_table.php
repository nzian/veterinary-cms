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
        //
        if(Schema::hasTable('tbl_vaccination_record')) {
            Schema::table('tbl_vaccination_record', function (Blueprint $table) {
                $table->string('dose')->after('vaccine_name')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        if(Schema::hasTable('tbl_vaccination_record')) {
            Schema::table('tbl_vaccination_record', function (Blueprint $table) {
                $table->dropColumn('dose');
            });
        }
    }
};
