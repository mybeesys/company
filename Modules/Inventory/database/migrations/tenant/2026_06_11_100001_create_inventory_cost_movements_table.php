<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_cost_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('establishment_id');
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('transaction_line_id')->nullable();
            $table->string('line_side', 16)->nullable();
            $table->string('movement_type', 32);
            $table->decimal('qty_delta', 24, 6)->default(0);
            $table->decimal('unit_cost', 24, 6)->default(0);
            $table->decimal('total_cost', 24, 6)->default(0);
            $table->decimal('average_cost_after', 24, 6)->default(0);
            $table->decimal('qty_on_hand_after', 24, 6)->default(0);
            $table->decimal('stock_value_after', 24, 6)->default(0);
            $table->dateTime('movement_date')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'product_id']);
            $table->index(['product_id', 'establishment_id']);
            $table->unique(
                ['transaction_id', 'transaction_line_id', 'line_side', 'product_id'],
                'icm_tx_line_product_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_cost_movements');
    }
};
