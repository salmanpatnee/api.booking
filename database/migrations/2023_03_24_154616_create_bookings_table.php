<?php

use App\Models\Employee;
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
            $table->string('reference_id')->unique();
            $table->foreignId('account_id')->constrained();
            $table->foreignIdFor(Employee::class, 'employee_id')->nullable();
            $table->string('device_name')->nullable();
            $table->string('imei')->nullable();
            $table->string('device_type')->nullable();
            $table->string('device_make')->nullable();
            $table->string('device_model')->nullable();
            $table->text('issue')->nullable();
            $table->string('issue_type')->nullable();
            $table->date('date');
            $table->date('delivered_date')->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->string('serial_no')->nullable();
            $table->text('customer_comments')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedDecimal('estimated_cost')->nullable();
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
