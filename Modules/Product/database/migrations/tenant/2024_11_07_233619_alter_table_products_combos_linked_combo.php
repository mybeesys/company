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
        Schema::table('product_product_combos', function (Blueprint $table) {
            $table->unsignedBigInteger('linked_combo_id')->nullable();
            $table->foreign('linked_combo_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('product_linked_combos');
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->unsignedInteger('order')->nullable();
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
