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
        Schema::table('inventory_Operations', function (Blueprint $table) {
          
            $table->unsignedBigInteger('establishment_id')->nullable();
            $table->foreign('establishment_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('est_establishments');
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
