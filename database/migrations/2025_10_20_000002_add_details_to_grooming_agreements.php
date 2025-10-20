<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('grooming_agreements', function (Blueprint $table) {
            $table->string('color_markings')->nullable()->after('signature_path');
            $table->text('history_before')->nullable()->after('color_markings');
            $table->text('history_after')->nullable()->after('history_before');
        });
    }

    public function down(): void
    {
        Schema::table('grooming_agreements', function (Blueprint $table) {
            $table->dropColumn(['color_markings', 'history_before', 'history_after']);
        });
    }
};
