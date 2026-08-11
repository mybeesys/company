<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('est_establishment_payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establishment_id')
                ->constrained('est_establishments')
                ->cascadeOnDelete();
            $table->string('payment_method_key', 50);
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounting_accounts')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['establishment_id', 'payment_method_key'],
                'est_pay_accounts_est_method_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('est_establishment_payment_accounts');
    }
};
