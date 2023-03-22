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
        Schema::create('product_exchanges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained();// new sale id
            $table->foreignId('sales_return_id')->constrained();// sales_return id
            $table->date('date');
            $table->unsignedDecimal('sale_amount');
            $table->unsignedDecimal('sales_return_amount');
            $table->decimal('net_amount');//sale amount - return amount
            $table->enum('status', ['draft', 'ordered', 'completed', 'cancelled', 'final']); //ordered,completed,canceled
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
        Schema::dropIfExists('product_exchanges');
    }
};
