<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_shippings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('transaction_id');
            $table->text('shipping_details')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_fees');
            $table->string('shipping_status')->nullable();
            $table->string('delivered_to')->nullable();
            $table->string('delivery_man')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_shippings');
    }
};