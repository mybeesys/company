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
        Schema::create('product_unit_transfer', function (Blueprint $table) {
            $table->id();
            $table->decimal('transfer');
            $table->unsignedBigInteger('unit1');
            $table->unsignedBigInteger('unit2');
            $table->unsignedBigInteger('objectId');
            $table->string('objectType');
            $table->boolean('primary');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('', function (Blueprint $table) {});
    }
};
