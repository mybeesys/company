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
        Schema::create('transaction_payments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('transaction_id');
            $table->integer('is_return')->nullable();
            $table->string('amount');
            $table->integer('method');
            $table->integer('payment_type')->nullable();
            $table->date('paid_on');
            $table->bigInteger('created_by');
            $table->text('note')->nullable();
            $table->string('payment_ref_no');
            $table->bigInteger('account_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_payments');
    }
};