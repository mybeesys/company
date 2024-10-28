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
        Schema::create('product_service_fee_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_fee_id');
            $table->unsignedBigInteger('payment_card_id');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('service_fee_id')
                ->references('id')
                ->on('product_service_fees');
            $table->foreign('payment_card_id')
                ->references('id')
                ->on('product_payments_cards');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
