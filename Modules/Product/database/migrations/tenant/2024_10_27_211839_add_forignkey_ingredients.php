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
        Schema::table('product_ingredients', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_measurement')->change();   
            $table->foreign('unit_measurement')
            ->references('id')
            ->on('product_unit');
            $table->softDeletes();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_ingredients', function (Blueprint $table) {
            
        });
    }
};
