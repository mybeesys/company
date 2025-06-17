<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('product_products', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->string('alertQuantity')->nullable();
            $table->string('defaultOrderQuantity')->nullable();
            $table->string('orderPriceWithTax')->nullable();
            $table->boolean('track_inventory')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_products', function (Blueprint $table) {

        });
    }
};
