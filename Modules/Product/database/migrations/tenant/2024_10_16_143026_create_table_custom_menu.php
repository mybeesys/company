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
            Schema::create('product_custom_menus', function (Blueprint $table) {
                $table->id();
                $table->string('name_ar')->unique();
                $table->string('name_en')->unique();
                $table->integer('application_type');
                $table->integer('mode')->nullable();
                $table->integer('station_id')->nullable();
                $table->boolean('active');
                $table->timestamps();
                $table->softDeletes(); // Adds a deleted_at column
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
