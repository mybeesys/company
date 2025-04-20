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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->string('status');
            $table->string('transfer_status')->nullable();
            $table->string('payment_status')->nullable();
            $table->bigInteger('contact_id')->nullable();
            $table->bigInteger('cost_center')->nullable();

            $table->string('ref_no')->nullable();
            $table->string('total_before_tax')->nullable();
            $table->string('tax_amount')->nullable();
            $table->string('discount_type')->nullable();
            $table->string('discount_amount')->nullable();
            $table->string('totalAfterDiscount')->nullable();
            $table->string('final_total')->nullable();
            $table->string('created_by')->nullable();
            $table->string('description')->nullable();
            $table->string('notice')->nullable();

            $table->date('transaction_date');

            $table->string('payment_terms')->nullable();
            $table->date('due_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
