<?php

namespace Modules\Accounting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Support\CoaColorSystem;
use Modules\Accounting\Utils\AccountingUtil;

class ChartOfAccountsTreePresenter
{
    /** @var Collection<int, true>|null */
    protected static ?Collection $movementAccountIds = null;

    /**
     * @param  iterable<int, AccountingAccount>  $accounts
     */
    public static function enrichAccountsCollection(iterable $accounts): void
    {
        foreach ($accounts as $account) {
            self::enrichAccount($account);
        }
    }

    public static function enrichAccount(AccountingAccount $account, int $inferredLevel = 3): void
    {
        $children = $account->relationLoaded('child_accounts')
            ? $account->child_accounts
            : collect();

        $level = (int) ($account->coa_level ?: $inferredLevel);
        $tone = CoaColorSystem::resolve($account->account_primary_type, $level);
        $account->setAttribute('coa_resolved_level', $tone['level']);
        $account->setAttribute('coa_tone_class', $tone['class']);
        $account->setAttribute('coa_tone_accent', $tone['accent']);
        $account->setAttribute('coa_tone_bg', $tone['background']);
        $account->setAttribute('coa_tone_fg', $tone['color']);

        foreach ($children as $child) {
            self::enrichAccount($child, $level + 1);
        }

        $hasChildren = $children->isNotEmpty();
        $hasMovements = self::movementAccountIds()->has($account->id);
        $directBalance = (float) ($account->balance ?? 0);

        $displayBalance = $hasChildren
            ? (float) $children->sum(fn (AccountingAccount $child) => (float) ($child->coa_display_balance ?? 0))
            : $directBalance;

        $account->setAttribute('coa_has_movements', $hasMovements);
        $account->setAttribute('coa_has_children', $hasChildren);
        $account->setAttribute('coa_can_add_child', ! $hasMovements);
        $account->setAttribute('coa_is_structure_violation', $hasMovements && $hasChildren);
        $account->setAttribute('coa_is_leaf', ! $hasChildren);
        $account->setAttribute('coa_direct_balance', $directBalance);
        $account->setAttribute('coa_display_balance', $displayBalance);
        $account->setAttribute('coa_balance_is_aggregated', $hasChildren);
    }

    /**
     * @param  array<string, string>  $accountTypes
     * @return array<string, array{label: string, glc: string, balance: float, accounts_count: int}>
     */
    public static function summarizeByPrimaryType(array $accountTypes, array $accountGlc): array
    {
        $balanceFormula = AccountingUtil::balanceFormula('AA');
        $parentIds = AccountingAccount::query()
            ->whereNotNull('parent_account_id')
            ->distinct()
            ->pluck('parent_account_id');

        $rows = AccountingAccount::query()
            ->select([
                'accounting_accounts.id',
                'accounting_accounts.account_primary_type',
                DB::raw("(SELECT $balanceFormula
                    FROM accounting_accounts_transactions AS AAT
                    JOIN accounting_accounts AS AA ON AAT.accounting_account_id = AA.id
                    WHERE AAT.accounting_account_id = accounting_accounts.id) AS balance"),
            ])
            ->when($parentIds->isNotEmpty(), fn ($q) => $q->whereNotIn('accounting_accounts.id', $parentIds))
            ->get();

        $summary = [];
        foreach ($accountTypes as $key => $label) {
            $typeRows = $rows->where('account_primary_type', $key);
            $summary[$key] = [
                'label' => $label,
                'glc' => $accountGlc[$key] ?? '',
                'balance' => (float) $typeRows->sum('balance'),
                'accounts_count' => $typeRows->count(),
            ];
        }

        return $summary;
    }

    /**
     * @return array{total: int, leaves: int, parents: int, with_movements: int, violations: int, active: int, inactive: int}
     */
    public static function treeStats(): array
    {
        $total = AccountingAccount::query()->count();
        $parentIds = AccountingAccount::query()
            ->whereNotNull('parent_account_id')
            ->distinct()
            ->pluck('parent_account_id');

        $withMovements = self::movementAccountIds()->count();
        $violations = AccountingAccount::query()
            ->whereIn('id', self::movementAccountIds()->keys())
            ->whereIn('id', $parentIds)
            ->count();

        return [
            'total' => $total,
            'leaves' => max(0, $total - $parentIds->count()),
            'parents' => $parentIds->count(),
            'with_movements' => $withMovements,
            'violations' => $violations,
            'active' => AccountingAccount::query()->where('status', 'active')->count(),
            'inactive' => AccountingAccount::query()->where('status', '!=', 'active')->count(),
        ];
    }

    public static function countStructureViolations(): int
    {
        return self::treeStats()['violations'];
    }

    /** @return Collection<int, true> */
    protected static function movementAccountIds(): Collection
    {
        if (self::$movementAccountIds === null) {
            self::$movementAccountIds = AccountingAccountsTransaction::query()
                ->distinct()
                ->pluck('accounting_account_id')
                ->filter()
                ->mapWithKeys(fn ($id) => [(int) $id => true]);
        }

        return self::$movementAccountIds;
    }
}
