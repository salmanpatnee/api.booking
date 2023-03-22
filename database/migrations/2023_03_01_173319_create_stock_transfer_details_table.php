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
        Schema::create('stock_transfer_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained();
            $table->foreignId('product_id')->constrained();
            
            /* how much boxes are there */
            $table->unsignedInteger('quantity_boxes')->nullable();
            /* how much units are in the boxes */
            $table->unsignedInteger('units_in_box')->nullable();

            $table->unsignedInteger('quantity');
            $table->double('price');
            $table->double('amount');
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
        Schema::dropIfExists('stock_transfer_details');
    }
};
