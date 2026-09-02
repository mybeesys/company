<?php

namespace Tests\Unit;

use Modules\Employee\Support\DashboardHubPermissions;
use PHPUnit\Framework\TestCase;

class DashboardHubPermissionsTest extends TestCase
{
    public function test_menu_covers_overview_and_module_dashboards(): void
    {
        $any = DashboardHubPermissions::menuShowAny();

        $this->assertContains(DashboardHubPermissions::DASHBOARD_SHOW, $any);
        $this->assertContains(DashboardHubPermissions::SALES_SHOW, $any);
        $this->assertContains(DashboardHubPermissions::PURCHASES_SHOW, $any);
        $this->assertContains(DashboardHubPermissions::PRODUCTS_SHOW, $any);
        $this->assertContains(DashboardHubPermissions::INVENTORY_SHOW, $any);
        $this->assertContains(DashboardHubPermissions::ACCOUNTING_SHOW, $any);
    }

    public function test_catalog_has_overview_without_inventing_all_row(): void
    {
        $catalog = include dirname(__DIR__, 2).'/Modules/Employee/data/dashboard-permissions.php';
        $names = array_column($catalog, 'name');

        $this->assertContains(DashboardHubPermissions::DASHBOARD_SHOW, $names);
        $this->assertNotContains('dashboard.all.show', $names);
        $this->assertNotContains('dashboard.Dashboard.create', $names);
    }
}
