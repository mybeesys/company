<?php

namespace Tests\Unit;

use Modules\Establishment\Models\EstablishmentInternalConsumptionType;
use Modules\Establishment\Services\EstablishmentInternalConsumptionTypeResolver;
use Modules\General\Models\Transaction;
use Tests\TestCase;

class EstablishmentInternalConsumptionTypeResolverTest extends TestCase
{
    public function test_calculate_charge_amount_uses_cogs_for_cost_type(): void
    {
        $type = new EstablishmentInternalConsumptionType([
            'value_type' => 'cost',
            'value' => null,
        ]);

        $transaction = new Transaction([
            'final_total' => 100,
            'total_before_tax' => 80,
        ]);

        $this->assertSame(42.5, EstablishmentInternalConsumptionTypeResolver::calculateChargeAmount($type, $transaction, 42.5));
    }

    public function test_calculate_charge_amount_uses_percent_of_final_total(): void
    {
        $type = new EstablishmentInternalConsumptionType([
            'value_type' => 'percent',
            'value' => 50,
        ]);

        $transaction = new Transaction([
            'final_total' => 200,
            'total_before_tax' => 150,
        ]);

        $this->assertSame(100.0, EstablishmentInternalConsumptionTypeResolver::calculateChargeAmount($type, $transaction, 30));
    }

    public function test_calculate_charge_amount_uses_fixed_value(): void
    {
        $type = new EstablishmentInternalConsumptionType([
            'value_type' => 'fixed',
            'value' => 25,
        ]);

        $transaction = new Transaction([
            'final_total' => 200,
        ]);

        $this->assertSame(25.0, EstablishmentInternalConsumptionTypeResolver::calculateChargeAmount($type, $transaction, 30));
    }
}
