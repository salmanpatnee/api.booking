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
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained();
            $table->foreignId('account_id')->constrained();
            $table->date('date');
            $table->unsignedDecimal('purchase_amount_before_return');//purchase amount before deducting return amount
            $table->unsignedDecimal('purchase_amount_after_return');// purchase amount after deducting return amount
            $table->unsignedDecimal('purchase_return_amount');// purchase_amount_before_return - purchase_amount_after_return
            $table->unsignedDecimal('received_amount');
            $table->unsignedInteger('products_count');
            $table->enum('payment_status', ['received', 'due']);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_returns');
    }
};
