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
        Schema::table('inventory_product_inventories', function (Blueprint $table) {
          
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->unsignedBigInteger('ingredient_id')->nullable();
            $table->foreign('ingredient_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_ingredients');
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
