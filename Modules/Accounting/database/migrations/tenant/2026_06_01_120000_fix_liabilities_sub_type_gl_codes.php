<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Revert mistaken 221/222 sub-types back to 21/22 (pattern: 2 → 21 → 2101).
        DB::table('accounting_account_types')
            ->where('account_type', 'sub_type')
            ->where('name_en', 'Current Liabilities')
            ->where('gl_code', '221')
            ->update(['gl_code' => '21']);

        DB::table('accounting_account_types')
            ->where('account_type', 'sub_type')
            ->where('name_en', 'Long-Term Liabilities')
            ->where('gl_code', '222')
            ->update(['gl_code' => '22']);

        DB::table('accounting_account_types')
            ->where('account_type', 'sub_type')
            ->where('name_en', 'Other Expenses')
            ->where('gl_code', '5012')
            ->update(['gl_code' => '512']);
    }

    public function down(): void
    {
        // no-op
    }
};
