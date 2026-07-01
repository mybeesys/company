<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\Report\Support\RegisterShiftReport;
use Tests\TestCase;

final class RegisterShiftReportTest extends TestCase
{
    public function test_payment_field_map_covers_seeded_payment_methods(): void
    {
        $this->assertSame('total_cash', RegisterShiftReport::PAYMENT_FIELD_MAP['cash']);
        $this->assertSame('total_card', RegisterShiftReport::PAYMENT_FIELD_MAP['card']);
        $this->assertSame('total_cheque', RegisterShiftReport::PAYMENT_FIELD_MAP['bank_check']);
        $this->assertSame('total_bank_transfer', RegisterShiftReport::PAYMENT_FIELD_MAP['bank_transfer']);
        $this->assertSame('total_advance', RegisterShiftReport::PAYMENT_FIELD_MAP['prepaid']);
    }

    public function test_merge_payment_totals_overwrites_register_details_fields(): void
    {
        $registerDetails = (object) [
            'total_cash' => 0,
            'total_cash_refund' => 0,
            'total_sale' => 0,
            'total_refund' => 0,
        ];

        $shiftTotals = (object) [
            'total_cash' => 150.5,
            'total_cash_refund' => 10,
            'total_card' => 200,
            'total_card_refund' => 0,
            'total_cheque' => 0,
            'total_cheque_refund' => 0,
            'total_bank_transfer' => 0,
            'total_bank_transfer_refund' => 0,
            'total_advance' => 0,
            'total_advance_refund' => 0,
            'total_sale' => 350.5,
            'total_refund' => 10,
        ];

        $merged = RegisterShiftReport::mergePaymentTotalsInto($registerDetails, $shiftTotals);

        $this->assertSame(150.5, $merged->total_cash);
        $this->assertSame(10.0, $merged->total_cash_refund);
        $this->assertSame(200.0, $merged->total_card);
        $this->assertSame(350.5, $merged->total_sale);
        $this->assertSame(10.0, $merged->total_refund);
    }
}
