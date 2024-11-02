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
        Schema::create('product_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar')->unique();
            $table->string('name_en')->unique();
            $table->boolean('active');
            $table->timestamps();
            $table->softDeletes(); // Adds a deleted_at column
        });
        Schema::create('product_groups_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_group_id');
            $table->unsignedBigInteger('product_id');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('product_group_id')
                ->references('id')
                ->on('product_groups');
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
