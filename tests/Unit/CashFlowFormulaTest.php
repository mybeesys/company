<?php

namespace Tests\Unit;

use Modules\Accounting\Support\CashFlowFormula;
use PHPUnit\Framework\TestCase;

class CashFlowFormulaTest extends TestCase
{
    public function test_wc_keys_map_to_asset_or_liability_formulas(): void
    {
        $this->assertSame(
            CashFlowFormula::WC_ASSET,
            CashFlowFormula::normalize('wc_inventory', 'wc_delta')
        );
        $this->assertSame(
            CashFlowFormula::WC_ASSET,
            CashFlowFormula::normalize('wc_inventory', 'wc_asset')
        );
        $this->assertSame(
            CashFlowFormula::WC_ASSET,
            CashFlowFormula::normalize('wc_prepaid_expenses', 'wc_asset')
        );
        $this->assertSame(
            CashFlowFormula::WC_LIABILITY,
            CashFlowFormula::normalize('wc_trade_payables', 'wc_delta')
        );
    }

    public function test_structure_keys_map_to_investing_and_financing_formulas(): void
    {
        $this->assertSame(
            CashFlowFormula::INVESTING_CAPEX,
            CashFlowFormula::normalize('inv_ppe_purchase', 'structure')
        );
        $this->assertSame(
            CashFlowFormula::INVESTING_PROCEEDS,
            CashFlowFormula::normalize('inv_ppe_sale', 'structure')
        );
        $this->assertSame(
            CashFlowFormula::FINANCING_INFLOW,
            CashFlowFormula::normalize('fin_capital', 'structure')
        );
        $this->assertSame(
            CashFlowFormula::FINANCING_OUTFLOW,
            CashFlowFormula::normalize('fin_dividends', 'structure')
        );
    }

    public function test_prior_period_is_not_treated_as_generic_adjustment(): void
    {
        $this->assertSame(
            CashFlowFormula::PRIOR_PERIOD,
            CashFlowFormula::normalize('prior_period_adjustments', 'adjustment')
        );
        $this->assertSame(
            CashFlowFormula::PRIOR_PERIOD,
            CashFlowFormula::normalize('prior_period_adjustments', 'prior_period')
        );
    }

    public function test_formula_labels_are_present_for_core_roles(): void
    {
        $this->assertNotSame('', CashFlowFormula::labelAr(CashFlowFormula::PL_NET));
        $this->assertNotSame('', CashFlowFormula::labelAr(CashFlowFormula::WC_ASSET));
        $this->assertNotSame('', CashFlowFormula::labelAr(CashFlowFormula::WC_LIABILITY));
    }
}
