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
        Schema::table('tbl_appoint_serv', function (Blueprint $table) {
            $table->string('coat_condition')->nullable()->after('serv_id');
            $table->json('skin_issues')->nullable()->after('coat_condition');
            $table->text('notes')->nullable()->after('skin_issues');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_appoint_serv', function (Blueprint $table) {
            $table->dropColumn(['coat_condition', 'skin_issues', 'notes']);
        });
    }
};