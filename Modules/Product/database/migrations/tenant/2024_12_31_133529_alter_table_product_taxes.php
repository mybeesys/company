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
        Schema::dropIfExists('product_taxes');
        Schema::table('product_products', function (Blueprint $table) {
            $table->unsignedBigInteger('tax_id')->nullable();
            $table->foreign('tax_id')
            ->references('id')
            ->on('taxes');
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
