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
        Schema::table('tbl_pet', function (Blueprint $table) {
            $table->foreign(['user_id'], 'fk_pet_user')->references(['user_id'])->on('tbl_user')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['own_id'])->references(['own_id'])->on('tbl_own')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_pet', function (Blueprint $table) {
            $table->dropForeign('fk_pet_user');
            $table->dropForeign('tbl_pet_own_id_foreign');
        });
    }
};
