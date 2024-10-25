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
        Schema::create('product_custommenu_times', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('custommenu_id');
            $table->date('from_date');
            $table->date('to_date')->nullable();
            $table->boolean('active');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('custommenu_id')
                ->references('id')
                ->on('product_custom_menus');
        });
        Schema::create('product_custommenu_times_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('custommenu_time_id');
            $table->integer('day_no');
            $table->string('from_time');
            $table->string('to_time');
            $table->boolean('active');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('custommenu_time_id')
                ->references('id')
                ->on('product_custommenu_times');
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
