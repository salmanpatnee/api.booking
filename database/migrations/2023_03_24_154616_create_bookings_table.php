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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('device_name');
            $table->string('model_no');
            $table->string('imei');
            $table->text('issue');
            $table->date('date');
            $table->foreignId('account_id')->constrained();
            $table->unsignedInteger('products_count');
            $table->unsignedDecimal('charges')->nullable();
            $table->unsignedDouble('purchase_amount')->nullable();
            $table->enum('status', ['inprocess', 'completed', 'customer collected - Payment pending', 'customer collected - CBR', 'cannot repaired', 'final'])->default('inprocess'); //ordered,completed,returned
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
        Schema::dropIfExists('bookings');
    }
};
