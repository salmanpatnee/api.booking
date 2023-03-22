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
        Schema::create('product_inventory_purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->string('reference_type', 128);
            $table->unsignedBigInteger('reference_id');
            $table->index(["reference_type", "reference_id"], null);
            $table->unsignedDouble('purchased_price');
            $table->unsignedInteger('purchased_quantity');
            $table->unsignedInteger('available_quantity');
            $table->date('expiry_date')->nullable();
            // $table->unsignedInteger('purchase_id')->nullable();
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
        Schema::dropIfExists('product_inventory_purchases');
    }
};
