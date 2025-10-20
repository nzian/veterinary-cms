<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grooming_agreements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visit_id');
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('pet_id');
            $table->string('signer_name');
            $table->string('signature_path');
            $table->string('consent_text_version')->default('v1');
            $table->boolean('checkbox_acknowledge')->default(false);
            $table->timestamp('signed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grooming_agreements');
    }
};
