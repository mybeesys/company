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
        Schema::create('cash_register_transactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('cash_register_id');
            $table->bigInteger('amount');
            $table->bigInteger('pay_method')->nullable();
            $table->bigInteger('type');
            $table->bigInteger('transaction_type')->nullable();
            $table->bigInteger('transaction_id ')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_register_transactions');
    }
};