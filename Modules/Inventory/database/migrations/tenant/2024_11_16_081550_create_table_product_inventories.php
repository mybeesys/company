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
        Schema::create('inventory_product_inventories', function (Blueprint $table) {
            $table->id();
            $table->decimal('threshold')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('primary_vendor_id')->nullable();
            $table->unsignedBigInteger('primary_vendor_unit_id')->nullable();
            $table->decimal('primary_vendor_default_quantity')->nullable();
            $table->decimal('primary_vendor_default_price')->nullable();
            $table->foreign('primary_vendor_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_vendors');
            $table->foreign('product_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_products');
            $table->foreign('unit_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_unit');
            $table->foreign('primary_vendor_unit_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_unit');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
