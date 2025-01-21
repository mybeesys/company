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
        Schema::create('reservation_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->boolean('active');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('reservation_tables', function (Blueprint $table) {
            $table->id();
            $table->string(column: 'code');
            //$table->string('name_ar');
            //$table->string('name_en');
            $table->string('steating_capacity');
            $table->string('table_status');
            $table->unsignedBigInteger('area_id');
            $table->boolean('active');
            $table->foreign('area_id')              // Foreign key constraint
            ->references('id')                    // References the id on the categories table
            ->on('reservation_areas');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_area');
    }
};
