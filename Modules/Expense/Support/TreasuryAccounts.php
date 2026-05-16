<?php

namespace Modules\Expense\Support;

use Illuminate\Database\Eloquent\Builder;
use Modules\Accounting\Models\AccountingAccount;

final class TreasuryAccounts
{
    /**
     * Payment source (credit) accounts: leaf rows under chart assets (cash/bank/treasury).
     * If EXPENSE_TREASURY_GL_CODES matches some rows, list is narrowed; otherwise all asset leaves.
     * Falls back to all active leaf accounts (same idea as payment vouchers) if no asset leaves exist.
     */
    public static function query(): Builder
    {
        $parentIds = AccountingAccount::query()
            ->whereNotNull('parent_account_id')
            ->pluck('parent_account_id')
            ->unique()
            ->filter()
            ->values();

        $codes = array_values(array_filter(array_map(
            'trim',
            (array) config('expense.treasury_gl_codes', [])
        ), fn (string $c) => $c !== ''));

        $leafAssets = AccountingAccount::query()
            ->where('status', 'active')
            ->where('account_primary_type', 'asset')
            ->when($parentIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $parentIds));

        if ($codes !== []) {
            $narrowed = (clone $leafAssets)->whereIn('gl_code', $codes);
            if ($narrowed->exists()) {
                return $narrowed->orderBy('gl_code');
            }
        }

        if ($leafAssets->exists()) {
            return $leafAssets->orderBy('gl_code');
        }

        return AccountingAccount::query()
            ->where('status', 'active')
            ->when($parentIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $parentIds))
            ->orderBy('gl_code');
    }

    public static function ids(): array
    {
        return static::query()->pluck('id')->all();
    }
}
