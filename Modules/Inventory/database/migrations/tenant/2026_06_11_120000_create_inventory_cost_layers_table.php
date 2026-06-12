<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_cost_layers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('establishment_id');
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('transaction_line_id')->nullable();
            $table->decimal('qty_remaining', 24, 6)->default(0);
            $table->decimal('unit_cost', 24, 6)->default(0);
            $table->dateTime('layer_date')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'establishment_id', 'layer_date'], 'icl_product_est_date_idx');
            $table->foreign('product_id')->references('id')->on('product_products');
            $table->foreign('establishment_id')->references('id')->on('est_establishments');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_cost_layers');
    }
};
