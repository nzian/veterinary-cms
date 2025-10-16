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
        Schema::table('tbl_own', function (Blueprint $table) {
            $table->foreign(['user_id'], 'fk_own_user')->references(['user_id'])->on('tbl_user')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_own', function (Blueprint $table) {
            $table->dropForeign('fk_own_user');
        });
    }
};
