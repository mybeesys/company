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
        Schema::create('establishment_divisions', function (Blueprint $table) {
            $table->id();
            $table->string('divisionName')->nullable();
            $table->foreignId('parentDivision_id')->nullable()->constrained('establishment_divisions')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('establishment_brands')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('divisions');
    }
};
