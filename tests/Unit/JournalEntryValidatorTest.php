<?php

namespace Tests\Unit;

use Illuminate\Validation\ValidationException;
use Modules\Accounting\Utils\JournalEntryValidator;
use Tests\TestCase;

class JournalEntryValidatorTest extends TestCase
{
    public function test_rejects_unbalanced_entry(): void
    {
        $this->expectException(ValidationException::class);

        JournalEntryValidator::validateAndNormalize([
            ['account_id' => 1, 'debit' => 10],
            ['account_id' => 2, 'credit' => 9.99],
        ]);
    }

    public function test_rejects_debit_and_credit_in_same_row(): void
    {
        $this->expectException(ValidationException::class);

        JournalEntryValidator::validateAndNormalize([
            ['account_id' => 1, 'debit' => 10, 'credit' => 10],
            ['account_id' => 2, 'credit' => 10],
        ]);
    }

    public function test_normalizes_and_balances_to_two_decimals(): void
    {
        $normalized = JournalEntryValidator::validateAndNormalize([
            ['account_id' => 1, 'debit' => 10],
            ['account_id' => 2, 'credit' => 10.0],
        ]);

        $this->assertCount(2, $normalized);
        $this->assertSame('debit', $normalized[0]['type']);
        $this->assertSame('10.00', $normalized[0]['amount']);
        $this->assertSame('credit', $normalized[1]['type']);
        $this->assertSame('10.00', $normalized[1]['amount']);
    }

    public function test_balances_three_decimal_lines_by_rounding_total_then_adjusting_last_line(): void
    {
        $normalized = JournalEntryValidator::validateAndNormalize([
            ['account_id' => 1, 'debit' => 822.955],
            ['account_id' => 1, 'debit' => 822.955],
            ['account_id' => 2, 'credit' => 1645.91],
        ]);

        $this->assertCount(3, $normalized);
        $this->assertSame('822.95', $normalized[1]['amount']);
        $this->assertSame('1645.91', $normalized[2]['amount']);

        $debitSum = (float) $normalized[0]['amount'] + (float) $normalized[1]['amount'];
        $this->assertEqualsWithDelta(1645.91, $debitSum, 0.001);
    }

    public function test_rejects_when_two_decimal_lines_are_genuinely_unbalanced(): void
    {
        $this->expectException(ValidationException::class);

        JournalEntryValidator::validateAndNormalize([
            ['account_id' => 1, 'debit' => 822.96],
            ['account_id' => 1, 'debit' => 822.96],
            ['account_id' => 2, 'credit' => 1645.91],
        ]);
    }

    public function test_accepts_entry_when_rounded_lines_balance(): void
    {
        $normalized = JournalEntryValidator::validateAndNormalize([
            ['account_id' => 1, 'debit' => 822.96],
            ['account_id' => 1, 'debit' => 822.96],
            ['account_id' => 2, 'credit' => 1645.92],
        ]);

        $this->assertCount(3, $normalized);
        $this->assertSame('1645.92', $normalized[2]['amount']);
    }
}
