<?php

namespace Modules\Expense\Support;

use Modules\Accounting\Models\AccountingAccount;

/**
 * Resolves the ledger row used for VAT input lines when posting expenses.
 */
final class VatLedgerAccount
{
    public static function resolve(): ?AccountingAccount
    {
        $configured = trim((string) config('expense.default_vat_gl_code'));
        $trimmed = ltrim($configured, '0');
        $candidates = array_values(array_unique(array_filter([
            $configured,
            $trimmed !== '' ? $trimmed : null,
            $configured !== '' && $trimmed !== '' ? str_pad($trimmed, strlen($configured), '0', STR_PAD_LEFT) : null,
        ], fn ($v) => $v !== null && $v !== '')));

        $base = AccountingAccount::query()->where('status', 'active');

        foreach ($candidates as $code) {
            $account = (clone $base)->where('gl_code', $code)->first();
            if ($account) {
                return $account;
            }
        }

        /** Fallback when charts don't match configured EXPENSE_VAT_GL (common tenant drift). */
        return (clone $base)
            ->where('account_primary_type', 'liabilities')
            ->where(function ($q) {
                $q->where('name_en', 'like', '%VAT%')
                    ->orWhere('name_ar', 'like', '%ضريبة القيمة المضافة%')
                    ->orWhere('name_ar', 'like', '%قيمة مضافة%');
            })
            ->orderBy('gl_code')
            ->first();
    }
}
