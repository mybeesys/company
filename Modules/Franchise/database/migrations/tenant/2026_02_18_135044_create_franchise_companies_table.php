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
        Schema::create('franchise_companies', function (Blueprint $table) {
    $table->id();
    $table->string('name_ar');
    $table->string('name_en');
    $table->string('city');
    $table->string('street')->nullable();
    $table->string('national_address')->nullable();
    $table->string('vat_no')->unique();
    $table->string('tel')->nullable();
    $table->string('mobile');
    $table->string('email')->unique();
    $table->string('account'); 
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('franchise_companies');
    }
};
