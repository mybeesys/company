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
        Schema::table('product_recipe_products', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_transfer_id')->nullable();
            $table->foreign('unit_transfer_id')
            ->references('id')
            ->on('product_unit_transfer');
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
