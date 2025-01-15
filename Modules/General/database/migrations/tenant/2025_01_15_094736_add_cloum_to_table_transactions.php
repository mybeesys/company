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

        Schema::table('transactions', function (Blueprint $table) {
            $table->bigInteger('establishment_id')->nullable();
        });

        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->bigInteger('unit_id')->nullable();
            $table->bigInteger('modifier_id')->nullable();
        });
        
        Schema::table('transactione_purchases_lines', function (Blueprint $table) {
            $table->bigInteger('unit_id')->nullable();
            $table->bigInteger('modifier_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {});
        Schema::table('transaction_sell_lines', function (Blueprint $table) {});
        Schema::table('transactione_purchases_lines', function (Blueprint $table) {});
    }
};