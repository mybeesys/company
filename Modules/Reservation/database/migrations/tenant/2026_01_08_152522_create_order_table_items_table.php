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
        Schema::create('order_table_items', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('transaction_id');
            $table->bigInteger('product_id');

            $table->string('qyt');
            $table->string('is_show')->nullable()->default('1');
            $table->string('remaining_qty')->default('0');
            $table->string('line_status')->nullable()->default('pending');

            $table->string('unit_price_before_discount');
            $table->string('unit_price');
            $table->string('discount_type')->nullable();
            $table->string('discount_amount')->nullable();
            $table->string('unit_price_inc_tax')->nullable();

            $table->string('tax_id')->nullable();
            $table->string('tax_value')->nullable();
            $table->string('total_before_vat')->nullable();

            $table->bigInteger('unit_id')->nullable();
            $table->bigInteger('modifier_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_table_items');
    }
};
