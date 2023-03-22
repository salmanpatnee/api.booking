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
        Schema::create('opharma_products_inventory', function (Blueprint $table) {
            $table->id();
            $table->string("product_id", 100)->nullable(); //old database reference id
            $table->unsignedInteger('purchased_quantity')->nullable();
            $table->unsignedInteger('sold_quantity')->nullable();
            $table->integer('adjusted_quantity')->nullable();
            $table->unsignedInteger('available_quantity')->nullable();
            $table->double('default_purchase_price')->nullable();
            $table->double('default_selling_price')->nullable();
            $table->unsignedInteger('uom_of_boxes')->nullable();
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
        Schema::dropIfExists('opharma_products_inventory');
    }
};
