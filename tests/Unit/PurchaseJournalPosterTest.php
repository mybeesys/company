<?php

namespace Tests\Unit;

use Modules\Accounting\Services\PurchaseJournalPoster;
use Modules\Accounting\Utils\AccountingUtil;
use RuntimeException;
use Tests\TestCase;

class PurchaseJournalPosterTest extends TestCase
{
    private function poster(): PurchaseJournalPoster
    {
        return new PurchaseJournalPoster(new AccountingUtil);
    }

    public function test_purchase_lines_balance_with_discount_and_vat(): void
    {
        // Gross 100, discount 10, VAT 13.5 on 90 → final 103.5
        $lines = [
            ['type' => 'credit', 'account_id' => 1, 'amount' => 103.5, 'role' => 'supplier_ap'],
            ['type' => 'debit', 'account_id' => 2, 'amount' => 100.0, 'role' => 'purchases'],
            ['type' => 'credit', 'account_id' => 3, 'amount' => 10.0, 'role' => 'earned_discount'],
            ['type' => 'debit', 'account_id' => 4, 'amount' => 13.5, 'role' => 'input_vat'],
        ];

        $this->poster()->assertProposedBalanced($lines);
        $this->assertTrue(true);
    }

    public function test_purchase_return_lines_balance_with_discount_reversal(): void
    {
        // Mirror return of the purchase above
        $lines = [
            ['type' => 'debit', 'account_id' => 1, 'amount' => 103.5, 'role' => 'supplier_ap'],
            ['type' => 'credit', 'account_id' => 2, 'amount' => 100.0, 'role' => 'purchase_return'],
            ['type' => 'debit', 'account_id' => 3, 'amount' => 10.0, 'role' => 'earned_discount_reversal'],
            ['type' => 'credit', 'account_id' => 4, 'amount' => 13.5, 'role' => 'input_vat_reversal'],
        ];

        $this->poster()->assertProposedBalanced($lines);
        $this->assertTrue(true);
    }

    public function test_rejects_inverted_discount_on_purchase(): void
    {
        // Wrong: debit earned discount on purchase (should be credit)
        $lines = [
            ['type' => 'credit', 'account_id' => 1, 'amount' => 103.5, 'role' => 'supplier_ap'],
            ['type' => 'debit', 'account_id' => 2, 'amount' => 100.0, 'role' => 'purchases'],
            ['type' => 'debit', 'account_id' => 3, 'amount' => 10.0, 'role' => 'earned_discount'],
            ['type' => 'debit', 'account_id' => 4, 'amount' => 13.5, 'role' => 'input_vat'],
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not balanced');
        $this->poster()->assertProposedBalanced($lines);
    }

    public function test_rejects_inverted_supplier_on_purchase(): void
    {
        // Wrong: debit supplier on purchase (should be credit AP)
        $lines = [
            ['type' => 'debit', 'account_id' => 1, 'amount' => 103.5, 'role' => 'supplier_ap'],
            ['type' => 'debit', 'account_id' => 2, 'amount' => 100.0, 'role' => 'purchases'],
            ['type' => 'credit', 'account_id' => 3, 'amount' => 10.0, 'role' => 'earned_discount'],
            ['type' => 'debit', 'account_id' => 4, 'amount' => 13.5, 'role' => 'input_vat'],
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not balanced');
        $this->poster()->assertProposedBalanced($lines);
    }

    public function test_resolve_amounts_prefers_header_gross_delta(): void
    {
        $tx = (object) [
            'final_total' => 103.5,
            'tax_amount' => 13.5,
            'total_before_tax' => 100,
            'totalAfterDiscount' => 90,
            'discount_amount' => 10,
            'discount_type' => 'fixed',
        ];

        $amounts = $this->poster()->resolveAmounts($tx);

        $this->assertSame(100.0, $amounts['gross_before_discount']);
        $this->assertSame(10.0, $amounts['discount_amount']);
        $this->assertSame(13.5, $amounts['tax_amount']);
        $this->assertSame(103.5, $amounts['final_total']);
    }
}
