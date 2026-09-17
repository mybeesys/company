<?php

namespace Tests\Unit;

use Modules\Employee\Services\ExecutiveDashboardService;
use PHPUnit\Framework\TestCase;

class ExecutiveDashboardServiceTest extends TestCase
{
    public function test_growth_percent_uses_previous_period_as_base(): void
    {
        $this->assertSame(25.0, ExecutiveDashboardService::growthPercent(125, 100));
        $this->assertSame(-50.0, ExecutiveDashboardService::growthPercent(50, 100));
    }

    public function test_growth_percent_when_previous_is_zero(): void
    {
        $this->assertSame(100.0, ExecutiveDashboardService::growthPercent(10, 0));
        $this->assertSame(0.0, ExecutiveDashboardService::growthPercent(0, 0));
    }

    public function test_net_margin_is_calculated_on_the_server(): void
    {
        $this->assertSame(26.3, ExecutiveDashboardService::netMarginPercent(263, 1000));
        $this->assertSame(0.0, ExecutiveDashboardService::netMarginPercent(50, 0));
    }

    public function test_widget_allowlist_is_isolated_from_legacy_hub(): void
    {
        $this->assertContains('kpis', ExecutiveDashboardService::WIDGETS);
        $this->assertContains('financial-trend', ExecutiveDashboardService::WIDGETS);
        $this->assertContains('product-health', ExecutiveDashboardService::WIDGETS);
        $this->assertContains('accounting-health', ExecutiveDashboardService::WIDGETS);
        $this->assertNotContains('overview', ExecutiveDashboardService::WIDGETS);
    }

    public function test_compact_slices_rolls_the_long_tail_into_others(): void
    {
        $slices = [];
        for ($i = 1; $i <= 10; $i++) {
            $slices[] = ['id' => $i, 'name' => 'C'.$i, 'value' => 110 - ($i * 10), 'share_percent' => 0, 'color' => '#000'];
        }

        $compact = ExecutiveDashboardService::compactSlices($slices, 6, 'أخرى');

        $this->assertCount(6, $compact);
        $this->assertSame('أخرى', $compact[5]['name']);
        $this->assertTrue($compact[5]['grouped']);
        $this->assertSame(150.0, (float) $compact[5]['value']);
    }

    public function test_empty_kpi_contract_keys_match_frontend_payload(): void
    {
        $keys = [
            'net_profit',
            'net_margin_pct',
            'total_expenses',
            'total_purchases',
            'total_sales',
            'low_stock_count',
            'high_turnover_count',
            'receivables_total',
            'receivables_overdue',
            'overdue_customers_count',
            'aov',
            'total_orders',
            'trends',
        ];
        $method = new \ReflectionMethod(ExecutiveDashboardService::class, 'emptyKpiContract');
        $method->setAccessible(true);
        $this->assertSame($keys, array_keys($method->invoke(new ExecutiveDashboardService)));
    }
}
