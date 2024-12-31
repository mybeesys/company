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
        Schema::create('group_sub_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_tax_id');
            $table->unsignedBigInteger('tax_id');
            $table->timestamps();

            $table->foreign('group_tax_id')->references('id')->on('taxes')->onDelete('cascade');
            $table->foreign('tax_id')->references('id')->on('taxes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_sub_taxes');
    }
};