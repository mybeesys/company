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
        Schema::create('product_discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->tinyInteger('function_id');
            $table->tinyInteger('discount_type');
            $table->decimal('amount')->nullable();
            $table->tinyInteger('qualification');
            $table->tinyInteger('qualification_type')->nullable();;
            $table->boolean('auto_apply');
            $table->boolean('item_level');
            $table->integer('required_product_count')->nullable();
            $table->integer('minimum_amount')->nullable();;
            $table->timestamps();
            $table->softDeletes(); // Adds a deleted_at column
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
