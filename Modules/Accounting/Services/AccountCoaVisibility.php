<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\AccountingAccount;

/**
 * Organizational COA/report visibility for contact ledger leaves.
 * Never applied to AccountingAccount::forDropdown() / select lists.
 */
final class AccountCoaVisibility
{
    private static ?bool $columnExists = null;

    public static function columnExists(): bool
    {
        if (self::$columnExists === null) {
            self::$columnExists = Schema::hasColumn('accounting_accounts', 'show_in_coa');
        }

        return self::$columnExists;
    }

    public static function isShownInCoa(object $account): bool
    {
        if (! self::columnExists()) {
            return true;
        }

        if (! isset($account->show_in_coa)) {
            return true;
        }

        return (bool) $account->show_in_coa;
    }

    /**
     * Flat BS/TB collections: roll hidden balances into the nearest visible ancestor, then drop hidden rows.
     *
     * @param  Collection<int, object>  $accounts
     * @return Collection<int, object>
     */
    public static function rollupHiddenIntoParents(Collection $accounts): Collection
    {
        if (! self::columnExists() || $accounts->isEmpty()) {
            return $accounts;
        }

        $byId = $accounts->keyBy(fn ($account) => (int) $account->id);

        foreach ($accounts as $account) {
            if (self::isShownInCoa($account)) {
                continue;
            }

            $balance = (float) ($account->balance ?? $account->amount ?? 0);
            if (abs($balance) < 0.00001) {
                continue;
            }

            $parentId = $account->parent_account_id ?? null;
            $guard = 0;
            while ($parentId && $byId->has((int) $parentId) && $guard < 12) {
                $parent = $byId->get((int) $parentId);
                if (self::isShownInCoa($parent)) {
                    if (isset($parent->balance)) {
                        $parent->balance = (float) $parent->balance + $balance;
                    } elseif (isset($parent->amount)) {
                        $parent->amount = (float) $parent->amount + $balance;
                    } else {
                        $parent->balance = $balance;
                    }
                    break;
                }
                $parentId = $parent->parent_account_id ?? null;
                $guard++;
            }
        }

        return $accounts->filter(fn ($account) => self::isShownInCoa($account))->values();
    }

    /**
     * Nested COA tree: remove hidden nodes and fold their subtree balances into the nearest visible parent.
     *
     * @param  Collection<int, AccountingAccount>  $nodes
     * @return Collection<int, AccountingAccount>
     */
    public static function collapseHiddenTree(Collection $nodes): Collection
    {
        if (! self::columnExists() || $nodes->isEmpty()) {
            return $nodes;
        }

        $kept = collect();
        foreach ($nodes as $node) {
            [$visible, $rolled] = self::collapseNode($node);
            if ($visible !== null) {
                $kept->push($visible);
            } elseif (abs($rolled) > 0.00001) {
                // Root-level hidden node — keep a zero-impact skip (no parent to receive roll).
            }
        }

        return $kept->values();
    }

    /**
     * @return array{0: ?AccountingAccount, 1: float}
     */
    private static function collapseNode(AccountingAccount $node): array
    {
        $children = $node->relationLoaded('child_accounts')
            ? $node->child_accounts
            : collect();

        $keptChildren = collect();
        $rolledFromHidden = 0.0;

        foreach ($children as $child) {
            [$keptChild, $roll] = self::collapseNode($child);
            if ($keptChild !== null) {
                $keptChildren->push($keptChild);
            } else {
                $rolledFromHidden += $roll;
            }
        }

        $node->setRelation('child_accounts', $keptChildren);
        $node->balance = (float) ($node->balance ?? 0) + $rolledFromHidden;

        $subtreeBalance = (float) $node->balance + self::sumTreeBalances($keptChildren);

        if (! self::isShownInCoa($node)) {
            return [null, $subtreeBalance];
        }

        return [$node, 0.0];
    }

    /**
     * @param  Collection<int, AccountingAccount>  $nodes
     */
    private static function sumTreeBalances(Collection $nodes): float
    {
        $total = 0.0;
        foreach ($nodes as $node) {
            $total += (float) ($node->balance ?? 0);
            if ($node->relationLoaded('child_accounts')) {
                $total += self::sumTreeBalances($node->child_accounts);
            }
        }

        return $total;
    }
}
