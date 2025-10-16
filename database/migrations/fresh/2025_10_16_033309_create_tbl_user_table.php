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
        Schema::create('tbl_user', function (Blueprint $table) {
            $table->bigIncrements('user_id');
            $table->string('user_name', 50);
            $table->string('user_email', 100);
            $table->string('user_contactNum', 20)->nullable();
            $table->string('user_licenseNum', 100)->nullable();
            $table->string('user_password', 100);
            $table->string('user_role', 50)->default('superadmin');
            $table->string('user_status', 20)->default('active');
            $table->unsignedBigInteger('branch_id')->nullable()->index('tbl_user_branch_id_foreign');
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_user');
    }
};
