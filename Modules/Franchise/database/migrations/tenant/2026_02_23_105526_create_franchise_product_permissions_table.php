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
        Schema::create('franchise_product_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('franchise_id')->constrained('franchise_companies')->onDelete('cascade');
            $table->unsignedBigInteger('permitted_id');
            $table->string('permitted_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('franchise_product_permissions');
    }
};
