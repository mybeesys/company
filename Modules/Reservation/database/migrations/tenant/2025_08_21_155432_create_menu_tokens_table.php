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
        Schema::create('menu_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('est_id');
            $table->string('title')->nullable();
            $table->string('sub_title')->nullable();
            $table->text('products');
            $table->string('cover')->nullable();
            $table->string('token')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_tokens');
    }
};
