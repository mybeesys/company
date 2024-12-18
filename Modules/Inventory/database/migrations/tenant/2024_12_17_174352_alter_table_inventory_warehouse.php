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
        Schema::table('inventory_warhouses', function (Blueprint $table) {
          
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('inventory_warhouses');
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
