<?php

namespace Tests\Unit;

use Modules\Accounting\Support\CashFlowStatementBlueprint;
use PHPUnit\Framework\TestCase;

class CashFlowStatementBlueprintTest extends TestCase
{
    public function test_blueprint_covers_excel_sections_and_core_coa_prefixes(): void
    {
        $lines = CashFlowStatementBlueprint::lines();
        $keys = array_column($lines, 'key');

        $this->assertContains('operating_header', $keys);
        $this->assertContains('investing_header', $keys);
        $this->assertContains('financing_header', $keys);
        $this->assertContains('net_operating', $keys);
        $this->assertContains('net_investing', $keys);
        $this->assertContains('net_financing', $keys);
        $this->assertContains('cash_opening', $keys);
        $this->assertContains('cash_closing', $keys);

        $byKey = [];
        foreach ($lines as $line) {
            $byKey[$line['key']] = $line;
        }

        $this->assertSame(['115'], $byKey['wc_inventory']['gl_prefixes']);
        $this->assertSame(['113'], $byKey['wc_trade_receivables']['gl_prefixes']);
        $this->assertSame(['211'], $byKey['wc_trade_payables']['gl_prefixes']);
        $this->assertSame(['121'], $byKey['inv_ppe_purchase']['gl_prefixes']);
        $this->assertSame(['111', '112'], $byKey['cash_closing']['gl_prefixes']);
        $this->assertSame('wc_delta', $byKey['wc_inventory']['amount_role']);
        $this->assertSame('pl_net', $byKey['net_profit_before_zakat']['amount_role']);
        $this->assertNotContains('32201', $byKey['net_profit_before_zakat']['gl_prefixes']);
        $this->assertSame('صافي ربح السنة / الفترة قبل الزكاة', $byKey['net_profit_before_zakat']['label_ar']);
        $this->assertSame('المخزون', $byKey['wc_inventory']['label_ar']);
    }

    public function test_detail_level_bounds(): void
    {
        $this->assertSame(3, CashFlowStatementBlueprint::DEFAULT_DETAIL_LEVEL);
        $this->assertSame(5, CashFlowStatementBlueprint::MAX_DETAIL_LEVEL);
    }
}
