<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'cost_center_id')) {
                $table->foreignId('cost_center_id')
                    ->nullable()
                    ->after('credit_accounting_account_id')
                    ->constrained('accounting_cost_centers')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            }
        });

        if (Schema::hasColumn('expenses', 'expense_category_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropForeign(['expense_category_id']);
                $table->dropColumn('expense_category_id');
            });
        }

        Schema::dropIfExists('expense_categories');
    }

    public function down(): void
    {
        if (! Schema::hasTable('expense_categories')) {
            Schema::create('expense_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'expense_category_id')) {
                $table->foreignId('expense_category_id')
                    ->nullable()
                    ->after('credit_accounting_account_id')
                    ->constrained('expense_categories')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            }

            if (Schema::hasColumn('expenses', 'cost_center_id')) {
                $table->dropForeign(['cost_center_id']);
                $table->dropColumn('cost_center_id');
            }
        });
    }
};
