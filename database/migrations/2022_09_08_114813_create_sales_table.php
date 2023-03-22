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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained();

            $table->date('date');
            $table->foreignId('account_id')->constrained();
            $table->unsignedInteger('products_count');

            // $table->enum('discount_type', ['fixed', 'percentage'])->nullable();
            // $table->unsignedDouble('discount_rate')->nullable();
            $table->unsignedDecimal('discount_amount')->nullable();

            $table->unsignedDecimal('gross_amount');
            $table->unsignedDecimal('net_amount');

            $table->unsignedDouble('purchase_amount')->nullable();

            $table->foreignId('payment_method_id')->constrained();
            $table->foreignId('bank_account_id')->nullable()->constrained();
            $table->enum('status', ['draft', 'ordered', 'completed', 'returned', 'final']); //ordered,completed,returned

            $table->unsignedDouble("paid_amount")->nullable();
            $table->unsignedDouble("returned_amount")->nullable();
            
            $table->boolean('is_deliverable')->default(false);

            $table->text('shipping_details')->nullable();
            $table->text('shipping_address')->nullable();

            $table->unsignedDecimal('shipping_charges')->nullable();

            $table->enum('shipping_status', ['ordered', 'packed', 'shipped', 'delivered', 'cancelled'])->nullable(); //in process,out for delivery

            $table->foreignId("bank_offer_id")->nullable()->constrained();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->unsignedBigInteger("ref_id")->nullable();//old database reference id

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
        Schema::dropIfExists('sales');
    }
};
