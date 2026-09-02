<?php

namespace Modules\Report\Support;

/**
 * Central general-reports EMS permission names (must match dashboard-permissions.php).
 *
 * Hub cards on /payment-reports each have show + print. reports_module.all.{action} ORs in.
 * Do not invent per-report create/update/delete.
 */
final class ReportPermissions
{
    public const ALL_SHOW = 'reports_module.all.show';

    public const ALL_PRINT = 'reports_module.all.print';

    public const ALL_CREATE = 'reports_module.all.create';

    public const ALL_UPDATE = 'reports_module.all.update';

    public const ALL_DELETE = 'reports_module.all.delete';

    public const SELL_PAYMENT_SHOW = 'reports_module.Sell payment report.show';

    public const SELL_PAYMENT_PRINT = 'reports_module.Sell payment report.print';

    public const PRODUCT_SALES_SHOW = 'reports_module.Product sales report.show';

    public const PRODUCT_SALES_PRINT = 'reports_module.Product sales report.print';

    public const SALES_COMPARISON_SHOW = 'reports_module.Sales comparison report.show';

    public const SALES_COMPARISON_PRINT = 'reports_module.Sales comparison report.print';

    public const WEEKDAY_SALES_SHOW = 'reports_module.Weekday sales report.show';

    public const WEEKDAY_SALES_PRINT = 'reports_module.Weekday sales report.print';

    public const PURCHASE_PAYMENT_SHOW = 'reports_module.Purchase payment report.show';

    public const PURCHASE_PAYMENT_PRINT = 'reports_module.Purchase payment report.print';

    public const PRODUCT_PURCHASE_SHOW = 'reports_module.Product purchase report.show';

    public const PRODUCT_PURCHASE_PRINT = 'reports_module.Product purchase report.print';

    public const PRODUCT_INVENTORY_SHOW = 'reports_module.Product inventory report.show';

    public const PRODUCT_INVENTORY_PRINT = 'reports_module.Product inventory report.print';

    public const PRODUCT_INVENTORY_SUMMARY_SHOW = 'reports_module.Product inventory summary.show';

    public const PRODUCT_INVENTORY_SUMMARY_PRINT = 'reports_module.Product inventory summary.print';

    public const PRODUCT_STOCK_SHOW = 'reports_module.Product stock report.show';

    public const PRODUCT_STOCK_PRINT = 'reports_module.Product stock report.print';

    public const PROFIT_LOSS_SHOW = 'reports_module.Profit Loss.show';

    public const PROFIT_LOSS_PRINT = 'reports_module.Profit Loss.print';

    public const PURCHASE_SELL_SHOW = 'reports_module.Purchase sell.show';

    public const PURCHASE_SELL_PRINT = 'reports_module.Purchase sell.print';

    public const REGISTER_SHOW = 'reports_module.Register report.show';

    public const REGISTER_PRINT = 'reports_module.Register report.print';

    /**
     * Hub card entities (no hidden movement report).
     *
     * @return list<string>
     */
    public static function entityKeys(): array
    {
        return [
            'sell_payment',
            'product_sales',
            'sales_comparison',
            'weekday_sales',
            'purchase_payment',
            'product_purchase',
            'product_inventory',
            'product_inventory_summary',
            'product_stock',
            'profit_loss',
            'purchase_sell',
            'register',
        ];
    }

    /**
     * @return array{show: string, print: string}
     */
    public static function crud(string $entity): array
    {
        return match ($entity) {
            'sell_payment', 'Sell payment report' => [
                'show' => self::SELL_PAYMENT_SHOW,
                'print' => self::SELL_PAYMENT_PRINT,
            ],
            'product_sales', 'Product sales report' => [
                'show' => self::PRODUCT_SALES_SHOW,
                'print' => self::PRODUCT_SALES_PRINT,
            ],
            'sales_comparison', 'Sales comparison report' => [
                'show' => self::SALES_COMPARISON_SHOW,
                'print' => self::SALES_COMPARISON_PRINT,
            ],
            'weekday_sales', 'Weekday sales report' => [
                'show' => self::WEEKDAY_SALES_SHOW,
                'print' => self::WEEKDAY_SALES_PRINT,
            ],
            'purchase_payment', 'Purchase payment report' => [
                'show' => self::PURCHASE_PAYMENT_SHOW,
                'print' => self::PURCHASE_PAYMENT_PRINT,
            ],
            'product_purchase', 'Product purchase report' => [
                'show' => self::PRODUCT_PURCHASE_SHOW,
                'print' => self::PRODUCT_PURCHASE_PRINT,
            ],
            'product_inventory', 'Product inventory report' => [
                'show' => self::PRODUCT_INVENTORY_SHOW,
                'print' => self::PRODUCT_INVENTORY_PRINT,
            ],
            'product_inventory_summary', 'Product inventory summary' => [
                'show' => self::PRODUCT_INVENTORY_SUMMARY_SHOW,
                'print' => self::PRODUCT_INVENTORY_SUMMARY_PRINT,
            ],
            'product_stock', 'Product stock report' => [
                'show' => self::PRODUCT_STOCK_SHOW,
                'print' => self::PRODUCT_STOCK_PRINT,
            ],
            'profit_loss', 'Profit Loss' => [
                'show' => self::PROFIT_LOSS_SHOW,
                'print' => self::PROFIT_LOSS_PRINT,
            ],
            'purchase_sell', 'Purchase sell' => [
                'show' => self::PURCHASE_SELL_SHOW,
                'print' => self::PURCHASE_SELL_PRINT,
            ],
            'register', 'Register report' => [
                'show' => self::REGISTER_SHOW,
                'print' => self::REGISTER_PRINT,
            ],
            default => throw new \InvalidArgumentException("Unknown reports EMS entity [{$entity}]"),
        };
    }

    /**
     * Route-name keys used by /payment-reports hub cards.
     *
     * @return array{show: string, print: string}
     */
    public static function report(string $key): array
    {
        return match ($key) {
            'sell-payment-report' => self::crud('sell_payment'),
            'product-sales-report' => self::crud('product_sales'),
            'sales-comparison-report' => self::crud('sales_comparison'),
            'weekday-sales-report' => self::crud('weekday_sales'),
            'purchase-payment-report' => self::crud('purchase_payment'),
            'product-purchase-report' => self::crud('product_purchase'),
            'product-inventory-report' => self::crud('product_inventory'),
            'product-inventory-summary' => self::crud('product_inventory_summary'),
            'Product-Stock-Report', 'product-stock-report' => self::crud('product_stock'),
            'Profit-Loss', 'profit-loss' => self::crud('profit_loss'),
            'purchase-sell' => self::crud('purchase_sell'),
            'Register-Report', 'register-report' => self::crud('register'),
            default => throw new \InvalidArgumentException("Unknown general report [{$key}]"),
        };
    }

    /**
     * Entity gate: entity permission OR reports_module.all.{action} (not a hub row).
     *
     * @return list<string>
     */
    public static function for(string $entity, string $action): array
    {
        $crud = self::crud($entity);
        if (! isset($crud[$action])) {
            throw new \InvalidArgumentException("Unknown reports EMS action [{$action}] for [{$entity}]");
        }

        return array_values(array_unique([
            $crud[$action],
            self::moduleAll($action),
        ]));
    }

    public static function moduleAll(string $action): string
    {
        return match ($action) {
            'show' => self::ALL_SHOW,
            'print' => self::ALL_PRINT,
            'create' => self::ALL_CREATE,
            'update' => self::ALL_UPDATE,
            'delete' => self::ALL_DELETE,
            default => throw new \InvalidArgumentException("Unknown reports EMS action [{$action}]"),
        };
    }

    /**
     * Sidebar / navbar / hub landing.
     *
     * @return list<string>
     */
    public static function menuShowAny(): array
    {
        return array_values(array_unique([
            self::ALL_SHOW,
            ...self::reportShows(),
        ]));
    }

    /**
     * @return list<string>
     */
    public static function reportShows(): array
    {
        return array_map(
            static fn (string $entity): string => self::crud($entity)['show'],
            self::entityKeys()
        );
    }

    /**
     * @return list<string>
     */
    public static function reportPrints(): array
    {
        return array_map(
            static fn (string $entity): string => self::crud($entity)['print'],
            self::entityKeys()
        );
    }

    /**
     * Shared branch / device / product lookups used by several reports.
     *
     * @return list<string>
     */
    public static function lookupShowAny(): array
    {
        return self::menuShowAny();
    }

    /**
     * @return list<string>
     */
    public static function salesLookupShowAny(): array
    {
        return array_values(array_unique([
            self::ALL_SHOW,
            self::SELL_PAYMENT_SHOW,
            self::PRODUCT_SALES_SHOW,
            self::SALES_COMPARISON_SHOW,
            self::WEEKDAY_SALES_SHOW,
        ]));
    }

    /**
     * @return list<string>
     */
    public static function purchaseLookupShowAny(): array
    {
        return array_values(array_unique([
            self::ALL_SHOW,
            self::PURCHASE_PAYMENT_SHOW,
            self::PRODUCT_PURCHASE_SHOW,
        ]));
    }

    /**
     * Comparison filter lookups (also used by weekday sales).
     *
     * @return list<string>
     */
    public static function comparisonLookupShowAny(): array
    {
        return array_values(array_unique([
            self::ALL_SHOW,
            self::SALES_COMPARISON_SHOW,
            self::WEEKDAY_SALES_SHOW,
        ]));
    }

    /**
     * Inventory report drill-down (record page) and related lookups.
     *
     * @return list<string>
     */
    public static function inventoryLookupShowAny(): array
    {
        return array_values(array_unique([
            self::ALL_SHOW,
            self::PRODUCT_INVENTORY_SHOW,
            self::PRODUCT_INVENTORY_SUMMARY_SHOW,
            self::PRODUCT_STOCK_SHOW,
        ]));
    }

    /**
     * @return list<array{name: string, name_ar: string, description: string, description_ar: string, type: string}>
     */
    public static function definitions(): array
    {
        $rows = array_filter(
            include base_path('Modules/Employee/data/dashboard-permissions.php'),
            static fn (array $row): bool => str_starts_with((string) ($row['name'] ?? ''), 'reports_module.')
        );

        $unique = [];
        foreach ($rows as $row) {
            $name = (string) ($row['name'] ?? '');
            if ($name === '' || isset($unique[$name])) {
                continue;
            }
            $unique[$name] = $row;
        }

        return array_values($unique);
    }
}
