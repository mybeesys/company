<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\Sales\Services\InvoiceServiceFeeAmounts;
use PHPUnit\Framework\TestCase;

final class InvoiceServiceFeeAmountsTest extends TestCase
{
    public function test_customer_facing_excludes_paid_fees(): void
    {
        $split = InvoiceServiceFeeAmounts::customerFacing([
            ['fee_direction' => 'COLLECTED', 'fee_amount' => 10, 'tax_amount' => 1.5, 'show_on_invoice' => true],
            ['fee_direction' => 'PAID', 'fee_amount' => 20, 'tax_amount' => 3, 'show_on_invoice' => false],
        ]);

        $this->assertEqualsWithDelta(10.0, $split['fee_amount'], 0.0001);
        $this->assertEqualsWithDelta(1.5, $split['fee_tax'], 0.0001);
    }

    public function test_visible_documents_honor_show_on_invoice(): void
    {
        $visible = InvoiceServiceFeeAmounts::visibleOnDocuments([
            ['fee_direction' => 'COLLECTED', 'fee_amount' => 10, 'tax_amount' => 1.5, 'show_on_invoice' => false],
            ['fee_direction' => 'COLLECTED', 'fee_amount' => 5, 'tax_amount' => 0, 'show_on_invoice' => true],
            ['fee_direction' => 'PAID', 'fee_amount' => 20, 'tax_amount' => 3, 'show_on_invoice' => true],
        ]);

        $this->assertCount(1, $visible);
        $this->assertEqualsWithDelta(5.0, (float) $visible[0]['fee_amount'], 0.0001);
    }
}
