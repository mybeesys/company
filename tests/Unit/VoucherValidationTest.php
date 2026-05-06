<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class VoucherValidationTest extends TestCase
{
    public function test_voucher_rejects_same_accounts(): void
    {
        $v = Validator::make([
            'account_id' => 10,
            'from_account' => 10,
            'paid_amount' => 1,
            'pament_on' => '2026-01-01',
        ], [
            'account_id' => ['required', 'integer', 'min:1'],
            'from_account' => ['required', 'integer', 'min:1', 'different:account_id'],
            'paid_amount' => ['required', 'numeric', 'gt:0'],
            'pament_on' => ['required', 'date'],
        ]);

        $this->assertTrue($v->fails());
    }
}
