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
        Schema::create('product_modifier_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('price_tier_id');
            $table->unsignedBigInteger('modifier_id');
            $table->foreign('modifier_id')
                ->references('id')
                ->on('product_modifiers');
            $table->foreign('price_tier_id')
                ->references('id')
                ->on('price_tiers');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::table('product_modifiers', function (Blueprint $table) {
            $table->string('SKU')->after('price');
            $table->string('barcode')->after('SKU');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modifier');
    }
};
