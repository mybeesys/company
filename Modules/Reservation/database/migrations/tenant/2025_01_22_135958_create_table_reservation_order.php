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
        Schema::create('reservation_orders', function (Blueprint $table) {
            $table->id();
            $table->string('no');
            $table->dateTime(column: 'order_date');
            $table->integer('order_status');
            $table->unsignedBigInteger(column: 'establishment_id');
            $table->unsignedBigInteger(column: 'table_id')->nullable();
            $table->foreign('establishment_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('est_establishments');
            $table->foreign('table_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('reservation_tables');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('reservation_order_items', function (Blueprint $table) {
            $table->id();
            $table->decimal(column: 'quantity');
            $table->decimal(column: 'item_price');
            $table->decimal(column: 'item_total_price');
            $table->unsignedBigInteger(column: 'order_id');
            $table->unsignedBigInteger(column: 'item_id');
            $table->foreign('order_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('reservation_orders');
            $table->foreign('item_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_products');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('');
    }
};
