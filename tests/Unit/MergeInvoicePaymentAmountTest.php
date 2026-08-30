<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Modules\General\Models\Transaction;
use Modules\General\Utils\TransactionUtils;
use Tests\TestCase;

class MergeInvoicePaymentAmountTest extends TestCase
{
    public function test_due_sell_return_sets_payment_amount_to_final_total(): void
    {
        $transaction = new Transaction;
        $transaction->forceFill([
            'type' => 'sell-return',
            'invoice_type' => 'due',
            'final_total' => 7.00,
        ]);

        $request = Request::create('/', 'POST', []);
        $result = (new TransactionUtils)->mergeInvoicePaymentAmount($transaction, $request);

        $this->assertSame(7.0, (float) $result->input('amount'));
    }

    public function test_due_purchases_return_sets_payment_amount_to_final_total(): void
    {
        $transaction = new Transaction;
        $transaction->forceFill([
            'type' => 'purchases-return',
            'invoice_type' => 'credit',
            'final_total' => 115.50,
        ]);

        $request = ['client_id' => 6];
        $result = (new TransactionUtils)->mergeInvoicePaymentAmount($transaction, $request);

        $this->assertSame(115.5, (float) $result['amount']);
    }

    public function test_due_sell_invoice_keeps_request_amount_unset(): void
    {
        $transaction = new Transaction;
        $transaction->forceFill([
            'type' => 'sell',
            'invoice_type' => 'due',
            'final_total' => 7.00,
        ]);

        $request = Request::create('/', 'POST', []);
        $result = (new TransactionUtils)->mergeInvoicePaymentAmount($transaction, $request);

        $this->assertNull($result->input('amount'));
    }
}
