<?php

namespace Modules\Accounting\Support;

use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountsRoting;
use Modules\Accounting\Support\MyBeeMasterCoaRules;

final class DefaultAccountRoutingMap
{
    public static function firstAccountByGlCandidates(array $codes): ?AccountingAccount
    {
        foreach ($codes as $code) {
            $account = AccountingAccount::query()->where('gl_code', (string) $code)->first();
            if ($account) {
                return $account;
            }
        }

        return null;
    }

    public static function firstAccountByGlOrCategory(array $codes, array $categories = []): ?AccountingAccount
    {
        $account = self::firstAccountByGlCandidates($codes);
        if ($account || $categories === []) {
            return $account;
        }

        return AccountingAccount::query()->whereIn('account_category', $categories)->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function routingRows(): array
    {
        $candidates = MyBeeMasterCoaRules::routingGlCandidates();

        $salesVat = self::firstAccountByGlCandidates($candidates['sales_vat']);
        $purchasesVat = self::firstAccountByGlCandidates($candidates['purchases_vat']) ?? $salesVat;
        $sales = self::firstAccountByGlCandidates($candidates['sales']);
        $salesReturn = self::firstAccountByGlCandidates($candidates['sales_return']) ?? $sales;
        $salesDiscount = self::firstAccountByGlCandidates($candidates['sales_discount']);
        $purchases = self::firstAccountByGlOrCategory($candidates['purchases'], ['COGS', 'cost_of_goods_sold']);
        $purchasesReturn = self::firstAccountByGlCandidates($candidates['purchases_return']) ?? $purchases;
        $purchasesDiscount = self::firstAccountByGlCandidates($candidates['purchases_discount']) ?? $salesDiscount;
        $inventory = self::firstAccountByGlOrCategory($candidates['inventory'], ['inventory']);
        $cogs = self::firstAccountByGlOrCategory($candidates['cogs'], ['COGS', 'cost_of_goods_sold']);
        $inventoryAdj = self::firstAccountByGlOrCategory(
            $candidates['inventory_adjustment'],
            ['inventory_adjustment', 'COGS']
        ) ?? $cogs;

        $now = now();
        $stamp = [
            'direction' => 'auto_assign',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        return [
            array_merge($stamp, [
                'type' => 'sales_vat_calculation',
                'section' => 'sales',
                'routing_type' => 'liability',
                'account_id' => $salesVat?->id,
            ]),
            array_merge($stamp, [
                'type' => 'purchases_purchase',
                'section' => 'purchases',
                'routing_type' => 'expense',
                'account_id' => $purchases?->id,
            ]),
            array_merge($stamp, [
                'type' => 'purchases_vat_calculation',
                'section' => 'purchases',
                'routing_type' => 'liability',
                'account_id' => $purchasesVat?->id,
            ]),
            array_merge($stamp, [
                'type' => 'sales_sales',
                'section' => 'sales',
                'routing_type' => 'revenue',
                'account_id' => $sales?->id,
            ]),
            array_merge($stamp, [
                'type' => 'sales_discount_calculation',
                'section' => 'sales',
                'routing_type' => 'expense',
                'account_id' => $salesDiscount?->id,
            ]),
            array_merge($stamp, [
                'type' => 'sales_discount_allowed',
                'section' => 'sales',
                'routing_type' => 'expense',
                'account_id' => $salesDiscount?->id,
            ]),
            array_merge($stamp, [
                'type' => 'purchases_discount_calculation',
                'section' => 'purchases',
                'routing_type' => 'expense',
                'account_id' => $purchasesDiscount?->id,
            ]),
            array_merge($stamp, [
                'type' => 'purchases_earned_discount',
                'section' => 'purchases',
                'routing_type' => 'expense',
                'account_id' => $purchasesDiscount?->id,
            ]),
            array_merge($stamp, [
                'type' => 'sales_sell_return',
                'section' => 'sales',
                'routing_type' => 'revenue',
                'account_id' => $salesReturn?->id,
            ]),
            array_merge($stamp, [
                'type' => 'purchases_purchase_return',
                'section' => 'purchases',
                'routing_type' => 'expense',
                'account_id' => $purchasesReturn?->id,
            ]),
            array_merge($stamp, [
                'type' => 'periodic_inventory_adjustment',
                'section' => 'periodic_inventory',
                'routing_type' => 'expense',
                'account_id' => $inventoryAdj?->id,
            ]),
            array_merge($stamp, [
                'type' => 'perpetual_inventory_asset',
                'section' => 'perpetual_inventory',
                'routing_type' => 'asset',
                'account_id' => $inventory?->id,
            ]),
            array_merge($stamp, [
                'type' => 'perpetual_inventory_cogs',
                'section' => 'perpetual_inventory',
                'routing_type' => 'expense',
                'account_id' => $cogs?->id,
            ]),
        ];
    }

    public static function seed(bool $replaceExisting = true): void
    {
        if ($replaceExisting) {
            AccountsRoting::query()->delete();
        } elseif (AccountsRoting::query()->exists()) {
            return;
        }

        AccountsRoting::query()->insert(self::routingRows());
    }
}
