<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trade_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string("address", 1024)->nullable();
            $table->double('balance');
            $table->enum('account_type', ['supplier', 'customer', 'both']);

            /* for supplier */
            $table->unsignedDecimal('purchases_amount', 16, 2)->nullable()->default(0.00);
            $table->unsignedInteger('purchases_count')->nullable();

            /* for customer */
            $table->unsignedDecimal('sales_amount', 16, 2)->nullable()->default(0.00);
            $table->unsignedInteger('sales_count')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['email', 'account_type']);
            $table->unique(['phone', 'account_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts');
    }
};
