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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('iso_code')->unique();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('dial_code');
            $table->string('currency_name_en')->nullable();
            $table->string('currency_symbol_en')->nullable();
            $table->string('currency_name_ar')->nullable();
            $table->string('currency_symbol_ar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};