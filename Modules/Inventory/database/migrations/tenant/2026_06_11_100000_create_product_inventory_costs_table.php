<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_inventory_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('establishment_id');
            $table->decimal('qty_on_hand', 24, 6)->default(0);
            $table->decimal('average_cost', 24, 6)->default(0);
            $table->decimal('stock_value', 24, 6)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'establishment_id'], 'pic_product_establishment_unique');
            $table->index('establishment_id');
            $table->foreign('product_id')->references('id')->on('product_products');
            $table->foreign('establishment_id')->references('id')->on('est_establishments');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_inventory_costs');
    }
};
