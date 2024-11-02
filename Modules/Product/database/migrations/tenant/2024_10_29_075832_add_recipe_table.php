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
        Schema::create('product_recipe_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ingredient_id');
            $table->unsignedBigInteger('product_id');
            $table->foreign('ingredient_id')
            ->references('id')
            ->on('product_ingredients');
           $table->foreign('product_id')
            ->references('id')
            ->on('product_products');
            $table->integer('quantity');
            $table->integer('order');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_recipe_modifiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ingredient_id');
            $table->unsignedBigInteger('modifier_id');
            $table->foreign('ingredient_id')
            ->references('id')
            ->on('product_ingredients');
           $table->foreign('modifier_id')
            ->references('id')
            ->on('product_modifiers');
            $table->integer('quantity');
            $table->integer('order');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_recipe_products');
        Schema::dropIfExists('product_recipe_modifiers');
    }
};
