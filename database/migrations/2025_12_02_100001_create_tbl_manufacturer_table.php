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
        Schema::create('tbl_manufacturer', function (Blueprint $table) {
            $table->id('manufacturer_id');
            $table->string('manufacturer_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add manufacturer_id column to products table
        Schema::table('tbl_prod', function (Blueprint $table) {
            $table->unsignedBigInteger('manufacturer_id')->nullable()->after('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_prod', function (Blueprint $table) {
            $table->dropColumn('manufacturer_id');
        });

        Schema::dropIfExists('tbl_manufacturer');
    }
};
