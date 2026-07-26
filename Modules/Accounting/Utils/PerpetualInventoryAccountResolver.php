<?php

namespace Modules\Accounting\Utils;

use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountsRoting;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Setting;

class PerpetualInventoryAccountResolver
{
    /**
     * Default inventory asset account (when the branch has no dedicated link).
     * Prefers Accounts Routing, then chart lookup by gl_code / category.
     */
    public static function defaultGlobalInventoryAssetAccountId(): ?int
    {
        $routedId = AccountsRoting::query()
            ->where('type', 'perpetual_inventory_asset')
            ->where('section', 'perpetual_inventory')
            ->value('account_id');

        if ($routedId) {
            return (int) $routedId;
        }

        return self::discoverInventoryAssetAccountIdFromChart();
    }

    /**
     * COGS expense account for perpetual inventory cost posting.
     * Prefers Accounts Routing, then chart lookup by gl_code / category.
     */
    public static function resolveCogsAccountId(): ?int
    {
        $routedId = AccountsRoting::query()
            ->where('type', 'perpetual_inventory_cogs')
            ->where('section', 'perpetual_inventory')
            ->value('account_id');

        if ($routedId) {
            return (int) $routedId;
        }

        return self::discoverCogsAccountIdFromChart();
    }

    public static function discoverInventoryAssetAccountIdFromChart(): ?int
    {
        return AccountingAccount::query()
            ->where(function ($q) {
                $q->where('gl_code', '1105')
                    ->orWhere('account_category', 'inventory');
            })
            ->value('id');
    }

    public static function discoverCogsAccountIdFromChart(): ?int
    {
        return AccountingAccount::query()
            ->where(function ($q) {
                $q->where('gl_code', '50101')
                    ->orWhere('account_category', 'COGS')
                    ->orWhere('account_category', 'cost_of_goods_sold');
            })
            ->value('id');
    }

    /**
     * Active detail (leaf) asset accounts — any sub-account under الأصول can be linked to a branch
     * for perpetual inventory credits (not limited to account_category = inventory).
     */
    public static function establishmentLinkableAssetAccounts(): \Illuminate\Support\Collection
    {
        $parentAccountIds = AccountingAccount::query()
            ->whereNotNull('parent_account_id')
            ->pluck('parent_account_id')
            ->unique()
            ->filter()
            ->values();

        return AccountingAccount::query()
            ->where('status', 'active')
            ->where('account_primary_type', 'asset')
            ->when($parentAccountIds->isNotEmpty(), fn ($q) => $q->whereNotIn('accounting_accounts.id', $parentAccountIds))
            ->orderBy('gl_code')
            ->get(['id', 'name_ar', 'name_en', 'gl_code', 'account_primary_type', 'account_category']);
    }

    /** @deprecated Use {@see establishmentLinkableAssetAccounts()} */
    public static function inventoryAssetAccountsForEstablishmentLink(): \Illuminate\Support\Collection
    {
        return self::establishmentLinkableAssetAccounts();
    }

    /**
     * GL account to **credit** for perpetual inventory (COGS / purchase asset side):
     * perpetual policy + branch (or ancestor branch) with a valid linked account → use it;
     * else default global inventory (1105 / inventory category).
     *
     * Uses {@see Establishment::withoutGlobalScopes()} so franchise rows and scopes do not hide the link,
     * and walks {@see Establishment::$parent_id} when the selling warehouse has no own link but a parent does.
     */
    public static function resolveInventoryAssetAccountId(?int $establishmentId): ?int
    {
        if (! Setting::isPerpetualInventory()) {
            return self::defaultGlobalInventoryAssetAccountId();
        }

        if ($establishmentId) {
            $linked = self::firstValidLinkedAccountWalkingAncestors((int) $establishmentId);
            if ($linked) {
                return $linked;
            }
        }

        return self::defaultGlobalInventoryAssetAccountId();
    }

    /**
     * Try this establishment, then each parent, until a usable linked GL account is found.
     */
    private static function firstValidLinkedAccountWalkingAncestors(int $establishmentId): ?int
    {
        $currentId = $establishmentId;
        $guard = 0;
        while ($currentId > 0 && $guard++ < 25) {
            $row = Establishment::withoutGlobalScopes()
                ->whereKey($currentId)
                ->first(['id', 'parent_id', 'perpetual_inventory_account_id']);
            if (! $row) {
                break;
            }
            $linked = $row->perpetual_inventory_account_id;
            if ($linked && self::isValidPerpetualInventoryCreditAccount((int) $linked)) {
                return (int) $linked;
            }
            $parentId = $row->parent_id;
            $currentId = $parentId ? (int) $parentId : 0;
        }

        return null;
    }

    /**
     * Linked account must exist and be usable as an inventory (asset) credit.
     * Accepts common data quirks: inactive status omitted (null), primary type null or "asset" case-insensitive.
     */
    public static function isValidPerpetualInventoryCreditAccount(int $accountId): bool
    {
        return AccountingAccount::query()
            ->whereKey($accountId)
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', '')
                    ->orWhere('status', 'active');
            })
            ->where(function ($q) {
                $q->whereNull('account_primary_type')
                    ->orWhereRaw('LOWER(account_primary_type) = ?', ['asset']);
            })
            ->exists();
    }
}
