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
        Schema::table('tbl_medical_history', function (Blueprint $table) {
            $table->foreign(['pet_id'])->references(['pet_id'])->on('tbl_pet')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_medical_history', function (Blueprint $table) {
            $table->dropForeign('tbl_medical_history_pet_id_foreign');
        });
    }
};
