<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * php artisan tenants:migrateRun the migrations.
     */
    public function up(): void
    {
        Schema::create('product_products', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->foreign('category_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('categories');               // Table to reference
            $table->foreign('subcategory_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('subcategories');
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->decimal('price');
            $table->decimal('cost');
            $table->string('class');
            $table->text('barcode');
            $table->string('SKU');
            $table->timestamps();
            $table->softDeletes(); // Adds a deleted_at column
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
