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
        Schema::create('product_attributeclass', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en'); 
            $table->integer('order');   
            $table->boolean('active');       
            $table->timestamps();
            $table->softDeletes(); // Adds a deleted_at column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attributeclass');
    }
};
