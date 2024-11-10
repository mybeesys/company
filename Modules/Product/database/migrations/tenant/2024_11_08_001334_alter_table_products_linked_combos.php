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
        Schema::table('product_products', function (Blueprint $table) {  
            $table->boolean('linked_combo');
            $table->tinyInteger('promot_upsell'); 
        });
        Schema::create('product_linked_combo_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');  
            $table->foreign('product_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_products');
            $table->unsignedBigInteger('linked_combo_id');  
            $table->foreign('linked_combo_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_linked_combos');  
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('product_linked_combos_upcharges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_combo_id');  
            $table->foreign('product_combo_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_linked_combo_items');
            $table->unsignedBigInteger('product_id');  
            $table->foreign('product_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_products');
            $table->decimal('price');
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
