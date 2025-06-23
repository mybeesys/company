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
        Schema::create('periodic_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periodic_inventory_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->decimal('system_quantity', 15, 3)->default(0);
            $table->decimal('physical_quantity', 15, 3)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('variance', 15, 3)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodic_inventory_items');

     }
};