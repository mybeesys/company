<?php

namespace Modules\Expense\Support;

use Illuminate\Database\Eloquent\Builder;
use Modules\Accounting\Models\AccountingAccount;

final class ExpenseLedgerAccounts
{
    /**
     * Leaf expense accounts from chart of accounts (debit side of expense vouchers).
     */
    public static function query(): Builder
    {
        $parentIds = AccountingAccount::query()
            ->whereNotNull('parent_account_id')
            ->pluck('parent_account_id')
            ->unique()
            ->filter()
            ->values();

        return AccountingAccount::query()
            ->where('status', 'active')
            ->whereIn('account_primary_type', ['expenses', 'expense'])
            ->when($parentIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $parentIds))
            ->orderBy('gl_code');
    }

    public static function ids(): array
    {
        return static::query()->pluck('id')->all();
    }
}
