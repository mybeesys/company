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
        Schema::create('product_serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number');
            $table->unsignedBigInteger('product_id'); 
            $table->foreign('product_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_serial_numbers'); 
            $table->string('status');   
            $table->timestamps();
            $table->softDeletes(); // Adds a deleted_at column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_numbers');
    }
};
