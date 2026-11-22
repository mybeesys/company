<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Support\MyBeeMasterCoaRules;

/**
 * Remove default export-customer placeholders (عميل تصدير 1..N) from existing trees.
 * Safe: skips any account that has children, ledger lines, or a linked contact.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounting_accounts')) {
            return;
        }

        $accounts = AccountingAccount::query()
            ->orderBy('id')
            ->get(['id', 'gl_code', 'name_ar', 'name_en', 'parent_account_id']);

        $targets = $accounts->filter(static function ($row) {
            // Only export-customer leaves; local customer/supplier already handled earlier.
            return MyBeeMasterCoaRules::isDefaultLocalPartyExampleLeaf(
                (string) ($row->name_ar ?? ''),
                (string) ($row->name_en ?? '')
            ) && (
                preg_match('/تصدير/u', (string) ($row->name_ar ?? '')) === 1
                || preg_match('/Export\s+Customer/i', (string) ($row->name_en ?? '')) === 1
            );
        });

        if ($targets->isEmpty()) {
            return;
        }

        $hasTxTable = Schema::hasTable('accounting_accounts_transactions');
        $hasContacts = Schema::hasTable('cs_contacts');

        foreach ($targets as $account) {
            $id = (int) $account->id;

            if (AccountingAccount::query()->where('parent_account_id', $id)->exists()) {
                continue;
            }

            if ($hasTxTable && DB::table('accounting_accounts_transactions')->where('accounting_account_id', $id)->exists()) {
                continue;
            }

            if ($hasContacts && DB::table('cs_contacts')->where('account_id', $id)->exists()) {
                continue;
            }

            AccountingAccount::query()->whereKey($id)->delete();
        }
    }

    public function down(): void
    {
        // Non-destructive removal — placeholders are not restored.
    }
};
