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
        Schema::create('product_modifiers', function (Blueprint $table) {
           
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->unsignedBigInteger('class_id');
            $table->foreign('class_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_modifierclasses'); 
            $table->decimal('price');
            $table->string('PLU');
            $table->string('color');
            $table->string('image');
            $table->timestamps();
            $table->softDeletes(); // Adds a deleted_at column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modifiers');
    }
};
