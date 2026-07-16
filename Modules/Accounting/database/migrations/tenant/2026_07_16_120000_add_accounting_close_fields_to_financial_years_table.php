<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_years', function (Blueprint $table) {
            if (! Schema::hasColumn('financial_years', 'accounting_close_journal_id')) {
                $table->unsignedBigInteger('accounting_close_journal_id')->nullable()->after('created_by');
            }
            if (! Schema::hasColumn('financial_years', 'accounting_closed_at')) {
                $table->timestamp('accounting_closed_at')->nullable()->after('accounting_close_journal_id');
            }
            if (! Schema::hasColumn('financial_years', 'accounting_closed_by')) {
                $table->unsignedBigInteger('accounting_closed_by')->nullable()->after('accounting_closed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('financial_years', function (Blueprint $table) {
            if (Schema::hasColumn('financial_years', 'accounting_closed_by')) {
                $table->dropColumn('accounting_closed_by');
            }
            if (Schema::hasColumn('financial_years', 'accounting_closed_at')) {
                $table->dropColumn('accounting_closed_at');
            }
            if (Schema::hasColumn('financial_years', 'accounting_close_journal_id')) {
                $table->dropColumn('accounting_close_journal_id');
            }
        });
    }
};
