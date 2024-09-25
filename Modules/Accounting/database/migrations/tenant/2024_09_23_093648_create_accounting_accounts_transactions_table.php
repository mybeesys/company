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
        Schema::create('accounting_accounts_transactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('accounting_account_id');
            $table->bigInteger('acc_trans_mapping_id')->nullable();
            $table->bigInteger('transaction_id')->nullable();
            $table->bigInteger('transaction_payment_id')->nullable();
            $table->integer('amount');
            $table->string('type');
            $table->string('sub_type');
            $table->string('map_type')->nullable();
            $table->bigInteger('created_by');
            $table->timestamp('operation_date');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_accounts_transactions');
    }
};
