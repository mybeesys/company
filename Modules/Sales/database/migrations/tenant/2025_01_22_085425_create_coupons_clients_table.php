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
        Schema::create('sales_coupons_clients', function (Blueprint $table) {
            $table->foreignId('client_id')->constrained('cs_contacts')->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('coupon_id')->constrained('sales_coupons')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['client_id', 'transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_coupons_clients');
    }
};
