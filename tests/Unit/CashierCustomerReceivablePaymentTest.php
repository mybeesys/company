<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use Modules\Sales\Services\CashierCustomerReceivablePayment;
use Modules\Sales\Services\PosSalesInvoiceMapper;
use Tests\TestCase;

final class CashierCustomerReceivablePaymentTest extends TestCase
{
    public function test_zero_or_missing_account_is_not_a_customer_receivable(): void
    {
        $this->assertFalse(CashierCustomerReceivablePayment::accountIdIsCustomerReceivable(null));
        $this->assertFalse(CashierCustomerReceivablePayment::accountIdIsCustomerReceivable(0));
        $this->assertNull(CashierCustomerReceivablePayment::contactIdForAccount(0));
    }

    public function test_explicit_pos_cash_method_is_not_treated_as_due(): void
    {
        $request = Request::create('/api/stor-sales-invoice', 'POST', [
            'invoice_type' => 'cash',
            'payment_status' => 'paid',
            'establishment_id' => 6,
            'purpose' => 'standard',
            'payments' => [
                ['method_id' => -1, 'amount' => 13],
            ],
        ]);

        $this->assertFalse(CashierCustomerReceivablePayment::invoiceIsDue($request));
        $this->assertSame('cash', PosSalesInvoiceMapper::resolveInvoiceType($request));
    }

    public function test_waste_and_internal_consumption_stay_off_the_due_path(): void
    {
        $payload = [
            'invoice_type' => 'cash',
            'establishment_id' => 6,
            'payments' => [
                ['method_id' => 9, 'amount' => 20],
            ],
        ];

        $waste = Request::create('/api/stor-sales-invoice', 'POST', $payload + ['purpose' => 'waste']);
        $this->assertFalse(CashierCustomerReceivablePayment::invoiceIsDue($waste));

        $internal = Request::create('/api/stor-sales-invoice', 'POST', $payload + [
            'purpose' => 'internal_consumption',
            'internal_consumption_type_id' => 3,
        ]);
        $this->assertFalse(CashierCustomerReceivablePayment::invoiceIsDue($internal));
    }
}
