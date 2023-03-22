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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('reference_number', 64)->unique();
            $table->foreignId('account_id')->constrained();

            $table->unsignedInteger('products_count');

            $table->unsignedDecimal('gross_amount', 12, 2);

            $table->enum('discount_type', ['fixed', 'percentage'])->nullable();
            $table->unsignedDouble('discount_rate')->nullable();
            $table->unsignedDecimal('discount_amount')->nullable();

            $table->unsignedDecimal('tax_amount')->nullable();

            /* Expense */
            // $table->unsignedDecimal('expense_amount');

            $table->unsignedDecimal('net_amount', 12, 2);
            $table->unsignedDecimal('paid_amount', 12, 2);            

            $table->enum('status', ['draft', 'ordered', 'received', 'returned', 'final']);
            $table->enum('payment_status', ['paid', 'due']);

            $table->foreignId('purchase_order_id')->nullable()->constrained();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->unsignedBigInteger("ref_id")->nullable();//old database reference id
            $table->string("remarks", 1024)->nullable();

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
        Schema::dropIfExists('purchases');
    }
};
