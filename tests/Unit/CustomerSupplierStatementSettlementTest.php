<?php

namespace Tests\Unit;

use Illuminate\Support\Collection;
use Modules\Accounting\Services\CustomerSupplierStatementReportService;
use ReflectionMethod;
use Tests\TestCase;

class CustomerSupplierStatementSettlementTest extends TestCase
{
    private function movement(
        int $id,
        string $type,
        float $amount,
        int $mappingId,
        string $subType = 'purchases',
        string $ref = '2026/0005'
    ): object {
        return new class($id, $type, $amount, $mappingId, $subType, $ref)
        {
            public $id;

            public $type;

            public $amount;

            public $acc_trans_mapping_id;

            public $sub_type;

            public $operation_date;

            public $note = null;

            public $transaction;

            public $costCenter = null;

            public $accTransMapping = null;

            public $createdBy = null;

            private string $ref;

            public function __construct($id, $type, $amount, $mappingId, $subType, $ref)
            {
                $this->id = $id;
                $this->type = $type;
                $this->amount = $amount;
                $this->acc_trans_mapping_id = $mappingId;
                $this->sub_type = $subType;
                $this->operation_date = '2026-03-20';
                $this->ref = $ref;
                $this->transaction = (object) [
                    'ref_no' => $ref,
                    'tax_amount' => 290.25,
                    'payment_status' => 'paid',
                    'establishment' => null,
                ];
            }

            public function displayRefNo(): string
            {
                return $this->ref;
            }

            public function ledgerDetailUrl(): ?string
            {
                return null;
            }
        };
    }

    /** @return array<int, array<string, mixed>> */
    private function buildLines(Collection $rows, bool $isDebitNature): array
    {
        $method = new ReflectionMethod(CustomerSupplierStatementReportService::class, 'buildStatementLines');
        $method->setAccessible(true);

        return $method->invoke(null, $rows, 0.0, $isDebitNature);
    }

    public function test_supplier_cash_invoice_settlement_is_visible_payment_row(): void
    {
        $rows = collect([
            $this->movement(1, 'credit', 2225.25, 100), // AP invoice
            $this->movement(2, 'debit', 2225.25, 100),  // AP settlement
        ]);

        $lines = $this->buildLines($rows, false); // supplier = credit nature
        $movements = array_values(array_filter($lines, fn ($l) => ($l['row_type'] ?? '') === 'movement'));

        $this->assertCount(2, $movements);

        $invoice = $movements[0];
        $settlement = $movements[1];

        $this->assertSame('invoice', $invoice['category']);
        $this->assertFalse($invoice['is_settlement']);
        $this->assertFalse($invoice['has_group_siblings']);

        $this->assertTrue($settlement['is_settlement']);
        $this->assertSame('payment', $settlement['category']);
        $this->assertSame(2225.25, $settlement['debit']);
        $this->assertSame(0.0, $settlement['credit']);
        $this->assertFalse($settlement['has_group_siblings']);
        $this->assertStringContainsString('settle', (string) $settlement['group_key']);
        $this->assertNotSame($invoice['group_key'], $settlement['group_key']);
    }

    public function test_customer_cash_invoice_settlement_is_credit_payment_row(): void
    {
        $rows = collect([
            $this->movement(1, 'debit', 1000.0, 50, 'sell'),
            $this->movement(2, 'credit', 1000.0, 50, 'sell'),
        ]);

        $lines = $this->buildLines($rows, true); // customer = debit nature
        $movements = array_values(array_filter($lines, fn ($l) => ($l['row_type'] ?? '') === 'movement'));

        $this->assertTrue($movements[1]['is_settlement']);
        $this->assertSame('payment', $movements[1]['category']);
        $this->assertSame(1000.0, $movements[1]['credit']);
        $this->assertFalse($movements[0]['is_settlement']);
        $this->assertSame('invoice', $movements[0]['category']);
    }

    public function test_credit_purchase_without_same_journal_settlement_stays_invoice_only(): void
    {
        $rows = collect([
            $this->movement(1, 'credit', 500.0, 10),
        ]);

        $lines = $this->buildLines($rows, false);
        $movements = array_values(array_filter($lines, fn ($l) => ($l['row_type'] ?? '') === 'movement'));

        $this->assertCount(1, $movements);
        $this->assertFalse($movements[0]['is_settlement']);
        $this->assertSame('invoice', $movements[0]['category']);
    }
}
