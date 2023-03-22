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
        // purchase_inventory_report
        Schema::create('product_inventory_entries', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('location_id')->constrained();
            $table->string('reference_type', 128);
            $table->unsignedBigInteger('reference_id');
            $table->index(["reference_type", "reference_id"], null);
            $table->unsignedDouble('purchased_price')->nullable();//for purchase
            $table->unsignedInteger('initial_quantity')->nullable();//for purchase
            $table->unsignedInteger('available_quantity')->nullable();//for purchase
            $table->unsignedInteger('sold_quantity')->nullable();//for purchase/sale
            $table->unsignedInteger('transferred_quantity')->nullable();//for purchase
            $table->integer('adjusted_quantity')->nullable();//for purchase
            $table->integer('returned_quantity')->nullable();//for purchase/sales returns
            $table->foreignId('product_inventory_entry_purchase_id')->nullable();//for sale/sales returns
            $table->date('expiry_date')->nullable();

            $table->unsignedInteger('purchase_returned_quantity')->nullable();//for purchase

            $table->unsignedDecimal('purchased_amount')->nullable();//for purchase
            $table->unsignedDecimal('sold_amount')->nullable();//for purchase

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
        Schema::dropIfExists('product_inventory_entries');
    }
};
