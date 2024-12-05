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
        Schema::create('inventory_Operations', function (Blueprint $table) {
            $table->id();
            $table->string('no');
            $table->tinyInteger('op_type');
            $table->tinyInteger('op_status');
            $table->dateTime('op_date');
            $table->string('notes')->nullable();
            $table->decimal('total');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('inventory_Operation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operation_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('ingredient_id')->nullable();
            $table->unsignedBigInteger('unit_id');
            $table->decimal('qty');
            $table->decimal('cost');
            $table->decimal('total');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('operation_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('inventory_Operations');
            $table->foreign('product_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_products');
            $table->foreign('ingredient_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_ingredients');
            $table->foreign('unit_id')
            ->references('id')
            ->on('product_unit_transfer');
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
