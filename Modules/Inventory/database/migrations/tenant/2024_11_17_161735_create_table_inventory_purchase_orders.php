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
        Schema::create('inventory_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('no');
            $table->unsignedBigInteger('vendor_id');
            $table->tinyInteger('po_status');
            $table->tinyInteger('invoice_status');
            $table->dateTime('po_date');
            $table->string('notes')->nullable();
            $table->decimal('tax')->nullable();
            $table->decimal('misc_amount')->nullable();
            $table->decimal('shipping_amount')->nullable();
            $table->decimal('total');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('vendor_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_vendors');
        });
        Schema::create('inventory_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('ingredient_id');
            $table->boolean('taxed');
            $table->unsignedBigInteger('unit_id');
            $table->decimal('qty');
            $table->decimal('cost');
            $table->decimal('total');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('product_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_products');
            $table->foreign('ingredient_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_ingredients');
            $table->foreign('unit_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_unit');
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
