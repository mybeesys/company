<?php

declare(strict_types=1);

namespace Modules\Accounting\Support;

/**
 * Formula roles for IFRS indirect cash-flow lines (accountant Excel rules).
 *
 * Assets / WC assets: prior − current (decrease = cash in).
 * Liabilities / equity financing: current − prior (increase = cash in).
 * P&L net: revenues − expenses for the selected period.
 * Adjustments: period non-cash charges added back.
 */
final class CashFlowFormula
{
    public const NONE = 'none';

    public const PL_NET = 'pl_net';

    public const ADJUSTMENT = 'adjustment';

    /** Prior-period adjustments line (not part of P&L net). */
    public const PRIOR_PERIOD = 'prior_period';

    public const WC_ASSET = 'wc_asset';

    public const WC_LIABILITY = 'wc_liability';

    public const INVESTING_ASSET = 'investing_asset';

    public const INVESTING_CAPEX = 'investing_capex';

    public const INVESTING_PROCEEDS = 'investing_proceeds';

    public const FINANCING_INFLOW = 'financing_inflow';

    public const FINANCING_OUTFLOW = 'financing_outflow';

    public const CASH_OPEN = 'cash_open';

    public const CASH_CLOSE = 'cash_close';

    public const NET_CHANGE = 'net_change';

    public const SECTION_NET = 'section_net';

    /**
     * Map legacy amount_role values and refine WC / structure lines.
     */
    public static function normalize(string $key, string $amountRole): string
    {
        if ($key === 'prior_period_adjustments' || $amountRole === 'prior_period') {
            return self::PRIOR_PERIOD;
        }

        return match ($amountRole) {
            'pl_net' => self::PL_NET,
            'adjustment' => self::ADJUSTMENT,
            'prior_period' => self::PRIOR_PERIOD,
            'wc_asset' => self::WC_ASSET,
            'wc_liability' => self::WC_LIABILITY,
            'cash_open' => self::CASH_OPEN,
            'cash_close' => self::CASH_CLOSE,
            'net_change' => self::NET_CHANGE,
            'section_net' => self::SECTION_NET,
            'none' => self::NONE,
            'wc_delta' => self::isLiabilityKey($key) ? self::WC_LIABILITY : self::WC_ASSET,
            'structure' => self::structureRole($key),
            default => $amountRole,
        };
    }

    public static function isLiabilityKey(string $key): bool
    {
        return str_starts_with($key, 'wc_') && in_array($key, [
            'wc_accrued_expenses',
            'wc_trade_payables',
            'wc_tax_payable',
            'wc_short_term_employee',
            'wc_customer_advances',
            'wc_st_loans',
            'wc_lease_current',
            'wc_st_provisions',
            'wc_partner_current',
            'wc_lt_provisions',
            'wc_eosb',
        ], true);
    }

    public static function structureRole(string $key): string
    {
        return match ($key) {
            'inv_ppe_purchase', 'inv_intangible_purchase' => self::INVESTING_CAPEX,
            'inv_ppe_sale', 'inv_intangible_disposal' => self::INVESTING_PROCEEDS,
            'inv_rou', 'inv_lt_investments', 'inv_lt_deposits', 'inv_cwip' => self::INVESTING_ASSET,
            'fin_dividends' => self::FINANCING_OUTFLOW,
            default => str_starts_with($key, 'fin_') ? self::FINANCING_INFLOW : self::INVESTING_ASSET,
        };
    }

    public static function labelAr(string $formula): string
    {
        return match ($formula) {
            self::PL_NET => 'إجمالي إيرادات الفترة − إجمالي مصروفات الفترة',
            self::ADJUSTMENT => 'مصروف الفترة (قيود الفترة) — يُضاف',
            self::PRIOR_PERIOD => 'مبلغ حساب «تعديلات سنوات سابقة» في ميزان المراجعة (أو 0)',
            self::WC_ASSET, self::INVESTING_ASSET => 'رصيد أول المدة − رصيد آخر المدة',
            self::WC_LIABILITY, self::FINANCING_INFLOW => 'رصيد آخر المدة − رصيد أول المدة',
            self::INVESTING_CAPEX => '−(التغير في الصافي + الاستهلاك)',
            self::INVESTING_PROCEEDS => 'متحصلات الاستبعاد خلال الفترة',
            self::FINANCING_OUTFLOW => '−مدفوعات الفترة',
            self::CASH_OPEN => 'رصيد النقد أول الفترة',
            self::CASH_CLOSE => 'رصيد النقد آخر الفترة',
            self::NET_CHANGE => 'تشغيلي + استثماري + تمويلي',
            self::SECTION_NET => 'مجموع بنود القسم',
            default => '',
        };
    }
}
