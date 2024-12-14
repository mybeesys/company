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
        Schema::create('transactione_purchases_lines', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('transaction_id');
            $table->bigInteger('product_id');
            $table->string('qyt');
            $table->string('unit_price_before_discount');
            $table->string('unit_price');
            $table->string('discount_type')->nullable();
            $table->string('discount_amount')->nullable();
            $table->string('unit_price_inc_tax')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('tax_value')->nullable();
            $table->string('total_before_vat')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactione_purchases_lines');
    }
};