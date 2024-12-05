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
        Schema::create('inventory_Op_purchaseOrder', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operation_id');
            $table->unsignedBigInteger('vendor_id');
            $table->tinyInteger('invoice_status');
            $table->decimal('tax')->nullable();
            $table->decimal('misc_amount')->nullable();
            $table->decimal('shipping_amount')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('vendor_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_vendors');
            $table->foreign('operation_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('inventory_Operations');
        });
         Schema::create('inventory_Op_purchaseOrder_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operation_item_id');
            $table->boolean('taxed');
            $table->decimal('recievd_qty')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('operation_item_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('inventory_Operation_items');
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
