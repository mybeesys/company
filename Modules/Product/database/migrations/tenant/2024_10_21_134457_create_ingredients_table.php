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
        Schema::create('product_ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->decimal('cost');
            $table->integer('unit_measurement');
            $table->string('SKU')->nullable();
            $table->string('barcode')->nullable();
            $table->boolean('active');
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->foreign('vendor_id')->references('id')->on('product_vendors');
            $table->unsignedBigInteger('reorder_point')->nullable();
            $table->unsignedBigInteger('reorder_quantity')->nullable();
            $table->decimal('yield_percentage')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_ingredients');
    }
};
