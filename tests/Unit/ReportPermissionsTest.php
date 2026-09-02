<?php

namespace Tests\Unit;

use Modules\Report\Support\ReportPermissions;
use PHPUnit\Framework\TestCase;

class ReportPermissionsTest extends TestCase
{
    public function test_maps_hub_reports_to_show_and_print_only(): void
    {
        $this->assertSame('reports_module.Sell payment report.show', ReportPermissions::crud('sell_payment')['show']);
        $this->assertSame('reports_module.Product sales report.print', ReportPermissions::crud('product_sales')['print']);
        $this->assertSame('reports_module.Sales comparison report.show', ReportPermissions::report('sales-comparison-report')['show']);
        $this->assertSame('reports_module.Weekday sales report.print', ReportPermissions::report('weekday-sales-report')['print']);
        $this->assertSame('reports_module.Purchase payment report.show', ReportPermissions::crud('purchase_payment')['show']);
        $this->assertSame('reports_module.Product purchase report.print', ReportPermissions::crud('product_purchase')['print']);
        $this->assertSame('reports_module.Product inventory report.show', ReportPermissions::crud('product_inventory')['show']);
        $this->assertSame('reports_module.Product inventory summary.print', ReportPermissions::crud('product_inventory_summary')['print']);
        $this->assertSame('reports_module.Product stock report.show', ReportPermissions::report('Product-Stock-Report')['show']);
        $this->assertSame('reports_module.Profit Loss.print', ReportPermissions::report('Profit-Loss')['print']);
        $this->assertSame('reports_module.Purchase sell.show', ReportPermissions::crud('purchase_sell')['show']);
        $this->assertSame('reports_module.Register report.print', ReportPermissions::crud('register')['print']);

        $this->assertArrayNotHasKey('create', ReportPermissions::crud('sell_payment'));
        $this->assertArrayNotHasKey('update', ReportPermissions::crud('register'));
        $this->assertArrayNotHasKey('delete', ReportPermissions::crud('profit_loss'));
        $this->assertCount(12, ReportPermissions::entityKeys());
    }

    public function test_for_ors_module_all_not_a_sibling_report(): void
    {
        $show = ReportPermissions::for('sell_payment', 'show');
        $this->assertContains(ReportPermissions::SELL_PAYMENT_SHOW, $show);
        $this->assertContains(ReportPermissions::ALL_SHOW, $show);
        $this->assertNotContains(ReportPermissions::PRODUCT_SALES_SHOW, $show);

        $print = ReportPermissions::for('profit_loss', 'print');
        $this->assertContains(ReportPermissions::PROFIT_LOSS_PRINT, $print);
        $this->assertContains(ReportPermissions::ALL_PRINT, $print);
        $this->assertNotContains(ReportPermissions::ALL_SHOW, $print);
    }

    public function test_menu_parent_covers_all_and_each_hub_report(): void
    {
        $any = ReportPermissions::menuShowAny();
        $this->assertContains(ReportPermissions::ALL_SHOW, $any);
        $this->assertContains(ReportPermissions::SELL_PAYMENT_SHOW, $any);
        $this->assertContains(ReportPermissions::REGISTER_SHOW, $any);
        $this->assertContains(ReportPermissions::PRODUCT_STOCK_SHOW, $any);
        $this->assertNotContains(ReportPermissions::ALL_PRINT, $any);
    }

    public function test_catalog_keeps_all_and_adds_hub_reports_without_inventing_names(): void
    {
        $catalog = include dirname(__DIR__, 2).'/Modules/Employee/data/dashboard-permissions.php';
        $names = array_column($catalog, 'name');

        foreach ([
            ReportPermissions::ALL_SHOW,
            ReportPermissions::ALL_PRINT,
            ReportPermissions::ALL_CREATE,
            ReportPermissions::ALL_UPDATE,
            ReportPermissions::ALL_DELETE,
            ReportPermissions::SELL_PAYMENT_SHOW,
            ReportPermissions::SELL_PAYMENT_PRINT,
            ReportPermissions::PRODUCT_SALES_SHOW,
            ReportPermissions::SALES_COMPARISON_PRINT,
            ReportPermissions::WEEKDAY_SALES_SHOW,
            ReportPermissions::PURCHASE_PAYMENT_PRINT,
            ReportPermissions::PRODUCT_PURCHASE_SHOW,
            ReportPermissions::PRODUCT_INVENTORY_SHOW,
            ReportPermissions::PRODUCT_INVENTORY_SUMMARY_PRINT,
            ReportPermissions::PRODUCT_STOCK_SHOW,
            ReportPermissions::PROFIT_LOSS_SHOW,
            ReportPermissions::PURCHASE_SELL_PRINT,
            ReportPermissions::REGISTER_SHOW,
            ReportPermissions::REGISTER_PRINT,
        ] as $name) {
            $this->assertContains($name, $names);
        }

        $this->assertNotContains('reports_module.Product movement report.show', $names);
        $this->assertNotContains('reports.Sell payment report.show', $names);
        $this->assertNotContains('reports_module.Sell payment report.create', $names);
        $this->assertNotContains('reports_module.cash_flow.show', $names);
    }

    public function test_rejects_entities_outside_hub_catalog(): void
    {
        foreach (['movement', 'cash_flow', 'devices'] as $entity) {
            try {
                ReportPermissions::crud($entity);
                $this->fail("Expected unknown reports EMS entity [{$entity}]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString($entity, $e->getMessage());
            }
        }
    }
}
