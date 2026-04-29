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
}

