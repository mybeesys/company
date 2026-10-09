<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\Accounting\Services\SellCashCollectionSplitter;
use PHPUnit\Framework\TestCase;

final class SellCashCollectionSplitterTest extends TestCase
{
    public function test_single_cash_payment_keeps_one_debit_on_that_account(): void
    {
        $splits = SellCashCollectionSplitter::split(
            [['account_id' => 10, 'amount' => 115.50]],
            10,
            115.50
        );

        $this->assertSame([
            ['account_id' => 10, 'amount' => 115.50],
        ], $splits);
    }

    public function test_splits_cash_and_bank_by_each_method_gl(): void
    {
        $splits = SellCashCollectionSplitter::split(
            [
                ['account_id' => 10, 'amount' => 40],
                ['account_id' => 20, 'amount' => 60],
            ],
            10,
            100
        );

        $this->assertSame([
            ['account_id' => 10, 'amount' => 40.0],
            ['account_id' => 20, 'amount' => 60.0],
        ], $splits);
        $this->assertEqualsWithDelta(100.0, array_sum(array_column($splits, 'amount')), 0.001);
    }

    public function test_merges_two_lines_on_the_same_gl(): void
    {
        $splits = SellCashCollectionSplitter::split(
            [
                ['account_id' => 10, 'amount' => 25],
                ['account_id' => 10, 'amount' => 15],
            ],
            99,
            40
        );

        $this->assertSame([
            ['account_id' => 10, 'amount' => 40.0],
        ], $splits);
    }

    public function test_web_invoice_without_payment_rows_uses_fallback_cash_account(): void
    {
        $splits = SellCashCollectionSplitter::split([], 10, 80);

        $this->assertSame([
            ['account_id' => 10, 'amount' => 80.0],
        ], $splits);
    }

    public function test_missing_line_account_uses_fallback(): void
    {
        $splits = SellCashCollectionSplitter::split(
            [
                ['account_id' => 0, 'amount' => 30],
                ['account_id' => 20, 'amount' => 70],
            ],
            10,
            100
        );

        $this->assertSame([
            ['account_id' => 10, 'amount' => 30.0],
            ['account_id' => 20, 'amount' => 70.0],
        ], $splits);
    }

    public function test_proportional_split_keeps_journal_collection_total(): void
    {
        // Payments include 3.50 collected fee that is stripped from the sales JE.
        $splits = SellCashCollectionSplitter::split(
            [
                ['account_id' => 10, 'amount' => 40],
                ['account_id' => 20, 'amount' => 63.50],
            ],
            10,
            100
        );

        $this->assertSame(10, $splits[0]['account_id']);
        $this->assertSame(20, $splits[1]['account_id']);
        $this->assertEqualsWithDelta(100.0, array_sum(array_column($splits, 'amount')), 0.001);
        $this->assertEqualsWithDelta(38.65, $splits[0]['amount'], 0.001);
        $this->assertEqualsWithDelta(61.35, $splits[1]['amount'], 0.001);
    }

    public function test_rounding_remainder_lands_on_last_account(): void
    {
        $splits = SellCashCollectionSplitter::split(
            [
                ['account_id' => 1, 'amount' => 1],
                ['account_id' => 2, 'amount' => 1],
                ['account_id' => 3, 'amount' => 1],
            ],
            1,
            10
        );

        $this->assertEqualsWithDelta(10.0, array_sum(array_column($splits, 'amount')), 0.001);
        $this->assertEqualsWithDelta(3.33, $splits[0]['amount'], 0.001);
        $this->assertEqualsWithDelta(3.33, $splits[1]['amount'], 0.001);
        $this->assertEqualsWithDelta(3.34, $splits[2]['amount'], 0.001);
    }
}
