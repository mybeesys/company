<?php

namespace Modules\Accounting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Utils\AccountingUtil;

class ChartOfAccountsTreeBuilder
{
    /**
     * Imported charts attach accounts via parent_account_id only (no account_sub_type_id).
     */
    public static function usesImportedChartLayout(): bool
    {
        if (! AccountingAccount::query()->exists()) {
            return false;
        }

        $withoutSubType = AccountingAccount::query()->whereNull('account_sub_type_id')->count();
        $total = AccountingAccount::query()->count();

        return $total > 0 && $withoutSubType >= (int) ceil($total * 0.5);
    }

    /**
     * @return array<string, Collection<int, AccountingAccount>>
     */
    public static function rootsByPrimaryType(): array
    {
        $balanceFormula = AccountingUtil::balanceFormula('AA');
        $accounts = AccountingAccount::query()
            ->select([
                DB::raw("(SELECT $balanceFormula
                    FROM accounting_accounts_transactions AS AAT
                    JOIN accounting_accounts AS AA ON AAT.accounting_account_id = AA.id
                    WHERE AAT.accounting_account_id = accounting_accounts.id) AS balance"),
                'accounting_accounts.*',
            ])
            ->orderBy('gl_code')
            ->get();

        /** @var array<int|string, Collection<int, AccountingAccount>> $byParent */
        $byParent = $accounts->groupBy(fn (AccountingAccount $account) => $account->parent_account_id ?? 'root');

        $attachChildren = function (AccountingAccount $account) use (&$attachChildren, $byParent): AccountingAccount {
            $children = ($byParent[$account->id] ?? collect())
                ->map(fn (AccountingAccount $child) => $attachChildren($child))
                ->values();

            $account->setRelation('child_accounts', $children);

            return $account;
        };

        $roots = ($byParent['root'] ?? collect())
            ->map(fn (AccountingAccount $account) => $attachChildren($account))
            ->values();

        $grouped = [];
        foreach ($roots as $root) {
            $type = (string) ($root->account_primary_type ?: 'asset');
            $grouped[$type] = ($grouped[$type] ?? collect())->push($root);
        }

        return $grouped;
    }
}
