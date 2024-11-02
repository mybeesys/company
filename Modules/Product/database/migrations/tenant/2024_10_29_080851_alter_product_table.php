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
            $table->boolean('prep_recipe')->nullable();
            $table->decimal('recipe_yield')->nullable();
        });

        Schema::table('product_modifiers', function (Blueprint $table) {
            $table->boolean('prep_recipe')->nullable();
            $table->decimal('recipe_yield')->nullable();
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
