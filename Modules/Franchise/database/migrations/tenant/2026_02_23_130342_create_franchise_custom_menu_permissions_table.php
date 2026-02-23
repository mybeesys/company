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
        Schema::create('franchise_custom_menu_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('franchise_id')->constrained('franchise_companies')->onDelete('cascade');
            $table->foreignId('custom_menu_id')->constrained('product_custom_menus')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('franchise_custom_menu_permissions');
    }
};
