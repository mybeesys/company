<?php

namespace Tests\Unit;

use Modules\Screen\Models\ScreenPairingPin;
use Tests\TestCase;

class ScreenPairingPinTest extends TestCase
{
    public function test_hash_pin_is_deterministic(): void
    {
        $a = ScreenPairingPin::hashPin('482913');
        $b = ScreenPairingPin::hashPin('482913');

        $this->assertSame($a, $b);
        $this->assertSame(64, strlen($a));
    }

    public function test_hash_pin_differs_for_different_pins(): void
    {
        $a = ScreenPairingPin::hashPin('482913');
        $b = ScreenPairingPin::hashPin('482914');

        $this->assertNotSame($a, $b);
    }
}
