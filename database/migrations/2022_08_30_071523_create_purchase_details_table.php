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
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained();
            $table->foreignId('product_id')->constrained();
            // $table->decimal('price_total');
            $table->unsignedDouble('price');//price_unit
            $table->unsignedInteger('quantity');//total quantity
            $table->decimal('amount');//total cost

            /* for uom */
            /* how much boxes are there */
            $table->unsignedInteger('quantity_boxes')->nullable();
            /* how much units are in the boxes */
            $table->unsignedInteger('units_in_box')->nullable();
            /* single box price */
            // $table->decimal('sale_price_box')->nullable();
            $table->unsignedInteger('quantity_strips')->nullable();
            $table->unsignedInteger('units_in_strip')->nullable();
            // $table->decimal('sale_price_strip')->nullable();
            $table->unsignedInteger('quantity_units')->nullable();

           
            $table->decimal('sale_price'); // unit sale price

            /* profit margin */
            $table->decimal('profit_margin');//((sale_price-price)/sale_price)*100

            $table->date('expiry_date')->nullable();

            $table->string("ref_id", 100)->nullable();//old database reference id

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_details');
    }
};
