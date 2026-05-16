<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debit_accounting_account_id')->constrained('accounting_accounts')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('credit_accounting_account_id')->constrained('accounting_accounts')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('tax_profile_id')->nullable()->constrained('expense_tax_profiles')->nullOnDelete();
            /** Stored: gross when tax > 0, otherwise full net amount */
            $table->decimal('amount', 21, 6);
            $table->decimal('tax', 21, 6)->default(0);
            $table->date('date')->index();
            $table->text('description');
            $table->json('attributes')->nullable();
            $table->json('meta')->nullable();
            $table->json('tax_profile_data')->nullable();
            $table->foreignId('acc_trans_mapping_id')->nullable()->constrained('accounting_acc_trans_mappings')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
