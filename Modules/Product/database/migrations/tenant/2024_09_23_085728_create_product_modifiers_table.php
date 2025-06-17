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
        Schema::create('product_product_modifiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')              // Foreign key constraint
                ->references('id')                    // References the id on the categories table
                ->on('product_products');


            $table->unsignedBigInteger('modifier_id');
            $table->foreign('modifier_id')              // Foreign key constraint
                ->references('id')                    // References the id on the categories table
                ->on('product_modifiers');

            $table->unsignedBigInteger('modifier_class_id');
            $table->foreign('modifier_class_id')
                ->references('id')
                ->on('product_modifierclasses');

            $table->boolean('active');
            $table->boolean('default');
            $table->integer('free_quantity');
            $table->integer('free_type');
            $table->integer('max_modifiers');
            $table->integer('min_modifiers');
            $table->string('button_display');
            $table->string('modifier_display');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_modifiers');
    }
};
