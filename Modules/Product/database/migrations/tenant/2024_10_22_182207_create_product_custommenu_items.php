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
        Schema::create('product_custommenu_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('custommenu_id');
            $table->unsignedBigInteger('product_id');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('custommenu_id')
                ->references('id')
                ->on('product_custom_menus');
            $table->foreign('product_id')
                ->references('id')
                ->on('product_products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('');
    }
};
