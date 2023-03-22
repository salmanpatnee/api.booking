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
        Schema::create('product_inventory_holders', function (Blueprint $table) {
            $table->id();                    

            $table->foreignId('sale_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('location_id')->constrained();
            $table->double('price');
            $table->unsignedInteger('sold_quantity')->nullable();
            $table->foreignId('product_inventory_entry_purchase_id')->nullable();

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
        Schema::dropIfExists('product_inventory_holders');
    }
};
