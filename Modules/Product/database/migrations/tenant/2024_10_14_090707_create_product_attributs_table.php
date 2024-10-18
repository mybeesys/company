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
        Schema::create('product_product_attribute', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id'); 
            $table->foreign('product_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_products');   
            $table->unsignedBigInteger('attribute_id1')->nullable(); 
            $table->foreign('attribute_id1')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_attributes');   
            $table->unsignedBigInteger('attribute_id2')->nullable(); 
            $table->foreign('attribute_id2')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_attributes'); 
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('barcode');
            $table->string('SKU');
            $table->decimal('price');
            $table->integer('starting');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_product_attribute');
    }
};
