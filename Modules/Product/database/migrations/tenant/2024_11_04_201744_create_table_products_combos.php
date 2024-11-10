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
        Schema::create('product_product_combos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');  
            $table->foreign('product_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_products');
            $table->string('name_ar');  
            $table->string('name_en'); 
            $table->string('barcode')->nullable(); 
            $table->boolean('combo_saving'); 
            $table->integer('quantity'); 
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('product_combos_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');  
            $table->foreign('item_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_products');
            $table->unsignedBigInteger('combo_id');  
            $table->foreign('combo_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_product_combos');
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
