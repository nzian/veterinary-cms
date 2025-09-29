<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tbl_branch
        Schema::create('tbl_branch', function (Blueprint $table) {
            $table->id('branch_id');
            $table->string('branch_address', 100)->nullable();
            $table->string('branch_contactNum', 15)->nullable();
            $table->string('branch_name', 50)->nullable();
        });

        // tbl_user
        Schema::create('tbl_user', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('user_name', 50);
            $table->string('user_email', 100);
            $table->string('user_password', 100);
            $table->string('user_role', 50)->default('superadmin');
            $table->string('user_status', 20)->default('active');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('registered_by')->nullable();

            $table->foreign('branch_id')->references('branch_id')->on('tbl_branch');
        });

        // tbl_prod
        Schema::create('tbl_prod', function (Blueprint $table) {
            $table->id('prod_id');
            $table->integer('prod_reorderlevel')->nullable();
            $table->integer('prod_stocks')->nullable();
            $table->float('prod_price')->nullable();
            $table->string('prod_category', 50)->nullable();
            $table->text('prod_description')->nullable();
            $table->string('prod_name', 50)->nullable();
            $table->unsignedBigInteger('ord_id')->nullable();
        });

        // tbl_ord
        Schema::create('tbl_ord', function (Blueprint $table) {
            $table->id('ord_id');
            $table->integer('ord_quantity')->nullable();
            $table->float('ord_total')->nullable();
            $table->date('ord_date')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('prod_id')->nullable();

            $table->foreign('user_id')->references('user_id')->on('tbl_user');
            $table->foreign('prod_id')->references('prod_id')->on('tbl_prod');
        });

        // tbl_bill
        Schema::create('tbl_bill', function (Blueprint $table) {
            $table->id('bill_id');
            $table->date('bill_date')->nullable();
            $table->unsignedBigInteger('ord_id')->nullable();
            $table->unsignedBigInteger('appoint_id')->nullable();
            $table->string('bill_status', 20)->default('Pending');

            $table->foreign('ord_id')->references('ord_id')->on('tbl_ord');
        });

        // tbl_pay
        Schema::create('tbl_pay', function (Blueprint $table) {
            $table->id('pay_id');
            $table->float('pay_change')->nullable();
            $table->float('pay_cashAmount')->nullable();
            $table->float('pay_total')->nullable();
            $table->unsignedBigInteger('bill_id')->nullable();

            $table->foreign('bill_id')->references('bill_id')->on('tbl_bill');
        });

        // tbl_own
        Schema::create('tbl_own', function (Blueprint $table) {
            $table->id('own_id');
            $table->string('own_location', 100)->nullable();
            $table->string('own_contactnum', 20)->nullable();
            $table->string('own_name', 50)->nullable();
        });

        // tbl_pet
        Schema::create('tbl_pet', function (Blueprint $table) {
            $table->id('pet_id');
            $table->float('pet_weight')->nullable();
            $table->string('pet_species', 50)->nullable();
            $table->string('pet_breed', 50)->nullable();
            $table->string('pet_age', 20)->nullable();
            $table->string('pet_name', 50)->nullable();
            $table->string('pet_gender', 10)->nullable();
            $table->date('pet_registration')->nullable();
            $table->float('pet_temperature')->nullable();
            $table->unsignedBigInteger('own_id')->nullable();

            $table->foreign('own_id')->references('own_id')->on('tbl_own');
        });

        // tbl_appoint
        Schema::create('tbl_appoint', function (Blueprint $table) {
            $table->id('appoint_id');
            $table->time('appoint_time')->nullable();
            $table->string('appoint_status', 50)->nullable();
            $table->date('appoint_date')->nullable();
            $table->text('appoint_description')->nullable();
            $table->string('appoint_type', 50)->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
        });

        // tbl_ref
        Schema::create('tbl_ref', function (Blueprint $table) {
            $table->id('ref_id');
            $table->date('ref_date')->nullable();
            $table->text('ref_description')->nullable();
            $table->unsignedBigInteger('appoint_id')->nullable();

            $table->foreign('appoint_id')->references('appoint_id')->on('tbl_appoint');
        });

        // tbl_serv
        Schema::create('tbl_serv', function (Blueprint $table) {
            $table->id('serv_id');
            $table->decimal('serv_price', 10, 2)->nullable();
            $table->text('serv_description')->nullable();
            $table->string('serv_type', 50)->nullable();
            $table->string('serv_name', 100)->nullable();
            
        });

        // tbl_appoint_serv
        Schema::create('tbl_appoint_serv', function (Blueprint $table) {
            $table->id('appoint_serv_id');
            $table->unsignedBigInteger('appoint_id')->nullable();
            $table->unsignedBigInteger('serv_id')->nullable();

            $table->foreign('appoint_id')->references('appoint_id')->on('tbl_appoint')->onDelete('cascade');
            $table->foreign('serv_id')->references('serv_id')->on('tbl_serv')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_appoint_serv');
        Schema::dropIfExists('tbl_serv');
        Schema::dropIfExists('tbl_ref');
        Schema::dropIfExists('tbl_appoint');
        Schema::dropIfExists('tbl_pet');
        Schema::dropIfExists('tbl_own');
        Schema::dropIfExists('tbl_pay');
        Schema::dropIfExists('tbl_bill');
        Schema::dropIfExists('tbl_ord');
        Schema::dropIfExists('tbl_prod');
        Schema::dropIfExists('tbl_user');
        Schema::dropIfExists('tbl_branch');
    }
};
