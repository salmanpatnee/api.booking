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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained();

            $table->string('name');
            $table->unsignedInteger('quantity')->default(0);

            $table->string('barcode')->unique();
            $table->text('description')->nullable();

            $table->unsignedDecimal('vat_amount')->nullable();

            $table->unsignedInteger('quantity_sold')->default(0);


            $table->double('default_purchase_price')->nullable();
            $table->double('default_selling_price')->nullable();

            $table->double('default_selling_price_old')->nullable();


            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');


            $table->timestamps();
            $table->softDeletes();

            // brand_id
            // barcode
            // sku
            // image
            // description
            // vat
            // units sold
            // alert_quantity
            // default purchase + selling price
            // is_active
            // created_by & updated_by
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
