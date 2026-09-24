<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Organizational visibility for contact (customer/supplier) GL accounts.
 * Selects / forDropdown are intentionally NOT filtered by this column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('accounting_accounts', 'show_in_coa')) {
                $table->boolean('show_in_coa')->default(true)->after('allow_direct_posting');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounting_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounting_accounts', 'show_in_coa')) {
                $table->dropColumn('show_in_coa');
            }
        });
    }
};
