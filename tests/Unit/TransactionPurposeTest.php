<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\Sales\Support\TransactionPurpose;
use PHPUnit\Framework\TestCase;

class TransactionPurposeTest extends TestCase
{
    public function test_normalize_defaults_to_standard(): void
    {
        $this->assertSame(TransactionPurpose::STANDARD, TransactionPurpose::normalize(null));
        $this->assertSame(TransactionPurpose::STANDARD, TransactionPurpose::normalize(''));
        $this->assertSame(TransactionPurpose::STANDARD, TransactionPurpose::normalize('standard'));
        $this->assertSame(TransactionPurpose::STANDARD, TransactionPurpose::normalize('unknown'));
    }

    public function test_normalize_maps_staff_meals_alias(): void
    {
        $this->assertSame(
            TransactionPurpose::INTERNAL_CONSUMPTION,
            TransactionPurpose::normalize('staff_meals')
        );
        $this->assertSame(
            TransactionPurpose::INTERNAL_CONSUMPTION,
            TransactionPurpose::normalize('internal_consumption')
        );
    }

    public function test_is_internal_consumption_on_object(): void
    {
        $tx = (object) ['purpose' => 'staff_meals'];
        $this->assertTrue(TransactionPurpose::isInternalConsumption($tx));

        $tx2 = (object) ['purpose' => 'standard'];
        $this->assertFalse(TransactionPurpose::isInternalConsumption($tx2));

        $tx3 = (object) ['purpose' => 'standard', 'internal_consumption_type_id' => 4];
        $this->assertTrue(TransactionPurpose::isInternalConsumption($tx3));
        $this->assertTrue(TransactionPurpose::isRequested('standard', 4));
        $this->assertFalse(TransactionPurpose::isRequested('standard', null));
    }
}
