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
        Schema::create('adjustment_entries', function (Blueprint $table) {
            $table->id();
            $table->date("date");
            $table->foreignId('product_id');
            $table->unsignedInteger('available_quantity');
            $table->unsignedInteger('physical_count');
            $table->integer('adjusted_quantity');
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
        Schema::dropIfExists('adjustment_entries');
    }
};
