<?php

namespace Modules\Employee\Support;

/**
 * Sidebar «لوحة التحكم» hub (/dashboard) — overview tab only.
 * Module dashboards stay sales.Dashboard / purchases.Dashboard / products.dashboard /
 * inventory.dashboard / accounting.Dashboard.
 */
final class DashboardHubPermissions
{
    public const DASHBOARD_SHOW = 'dashboard.Dashboard.show';

    public const SALES_SHOW = 'sales.Dashboard.show';

    public const PURCHASES_SHOW = 'purchases.Dashboard.show';

    public const PRODUCTS_SHOW = 'products.dashboard.show';

    public const INVENTORY_SHOW = 'inventory.dashboard.show';

    public const ACCOUNTING_SHOW = 'accounting.Dashboard.show';

    /**
     * Sidebar item and GET /dashboard: overview or any module dashboard tab.
     *
     * @return list<string>
     */
    public static function menuShowAny(): array
    {
        return [
            self::DASHBOARD_SHOW,
            self::SALES_SHOW,
            self::PURCHASES_SHOW,
            self::PRODUCTS_SHOW,
            self::INVENTORY_SHOW,
            self::ACCOUNTING_SHOW,
        ];
    }

    /**
     * @return list<array{name: string, name_ar: string, description: string, description_ar: string, type: string}>
     */
    public static function definitions(): array
    {
        $rows = array_filter(
            include base_path('Modules/Employee/data/dashboard-permissions.php'),
            static fn (array $row): bool => str_starts_with((string) ($row['name'] ?? ''), 'dashboard.')
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
