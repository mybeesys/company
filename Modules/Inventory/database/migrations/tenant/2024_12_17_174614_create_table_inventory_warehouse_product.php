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
        Schema::create('inventory_warhouse_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warhouse_id');
            $table->unsignedBigInteger('product_id');
            $table->foreign('warhouse_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('inventory_warhouses');
            $table->foreign('product_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_products');
            $table->softDeletes();
            $table->timestamps();
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
