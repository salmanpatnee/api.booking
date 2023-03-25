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
            $table->string('booking_id')->unique();
            $table->foreignId('account_id')->constrained();
            $table->foreignId('employee_id')->constrained();
            $table->string('device_name');
            $table->string('model_no');
            $table->string('imei');
            $table->text('issue');
            $table->date('date');
            $table->date('delivered_date')->nullable();
            $table->unsignedDecimal('charges')->nullable();
            $table->unsignedDouble('purchase_amount')->nullable();
            $table->enum('status', ['in progress', 'repaired', 'complete', 'can not be repaired', 'customer collected CBR', 'customer collected payment pending', 'shop property', 'awaiting customer response', 'awaiting parts'])->default('in progress'); //ordered,completed,returned
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
