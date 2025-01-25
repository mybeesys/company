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
        Schema::create('sales_coupons_types', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained('sales_coupons')->cascadeOnDelete();
            $table->morphs('applicable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_coupons_types');
    }
};
