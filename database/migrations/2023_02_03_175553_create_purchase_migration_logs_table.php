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
        Schema::create('purchase_migration_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opharma_purchase_id');
            $table->string('opharma_purchase_detail_id', 100)->nullable();
            $table->string('opharma_product_id', 100)->nullable();
            $table->string("remarks", 1024);
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
        Schema::dropIfExists('purchase_migration_logs');
    }
};
