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
        Schema::create('sale_migration_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opharma_invoice_id');
            $table->string('opharma_invoice_detail_id', 100)->nullable();
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
        Schema::dropIfExists('sale_migration_logs');
    }
};
