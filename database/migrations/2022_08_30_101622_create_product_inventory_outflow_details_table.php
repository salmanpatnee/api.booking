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
        Schema::create('product_inventory_outflow_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_inventory_outflow_id');
            $table->foreign('product_inventory_outflow_id', 'outflow_details_outflow_id')
                ->references('id')->on('product_inventory_outflows');
            $table->foreignId('product_id')->constrained();

            $table->foreignId('product_inventory_purchase_id');
            $table->foreign('product_inventory_purchase_id', 'outflow_details_inventory_purchase_id')
                ->references('id')
                ->on('product_inventory_purchases');

            $table->unsignedInteger('quantity');

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
        Schema::dropIfExists('product_inventory_outflow_details');
    }
};
