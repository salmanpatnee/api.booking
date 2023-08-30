<?php

use App\Models\BookingListDetails;
use App\Models\Part;
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
        Schema::create('booking_item_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(BookingListDetails::class, 'booking_list_details_id');
            $table->foreignIdFor(Part::class, 'part_id');
            $table->unsignedInteger('quantity');
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('total', 10, 2);
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
        Schema::dropIfExists('booking_item_parts');
    }
};
