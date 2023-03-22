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
        Schema::create('bank_offers', function (Blueprint $table) {
            $table->id();

            $table->foreignId("bank_id")->constrained();
            // $table->foreignId("bank_card_id")->constrained();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->unsignedInteger('amount_limit')->nullable();
            $table->unsignedInteger('orders_limit')->nullable();

            $table->enum('discount_type', ["fixed", "percentage"])->nullable();
            $table->decimal('discount_amount')->nullable();
            $table->decimal('discount_percentage')->nullable();

            $table->unsignedInteger('count')->default(0);

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

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
        Schema::dropIfExists('bank_offers');
    }
};
