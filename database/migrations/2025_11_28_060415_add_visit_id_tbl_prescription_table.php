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
        if(!Schema::hasColumn('tbl_prescription', 'visit_id')){
            Schema::table('tbl_prescription', function (Blueprint $table) {
                $table->unsignedBigInteger('pres_visit_id')->nullable()->after('user_id');
            });
            Schema::table('tbl_prescription', function (Blueprint $table) {
                $table->foreign('pres_visit_id')->references('visit_id')->on('tbl_visit_record')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        if(Schema::hasColumn('tbl_prescription', 'pres_visit_id')){
        Schema::table('tbl_prescription', function (Blueprint $table) {
            $table->dropForeign(['pres_visit_id']);
            $table->dropColumn('pres_visit_id');
        });
    }
    }
};
