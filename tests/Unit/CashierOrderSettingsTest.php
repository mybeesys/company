<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\Establishment\Services\CashierOrderSettings;
use PHPUnit\Framework\TestCase;

final class CashierOrderSettingsTest extends TestCase
{
    public function test_defaults_use_daily_central_sequence_without_cycling(): void
    {
        $defaults = CashierOrderSettings::defaults();

        $this->assertTrue($defaults['enabled']);
        $this->assertSame('daily', $defaults['reset_policy']);
        $this->assertSame('central', $defaults['allocation']);
        $this->assertFalse($defaults['cycle_enabled']);
        $this->assertSame('dated_serial', $defaults['system_id_mode']);
    }

    public function test_preview_pads_and_prefixes_display_number(): void
    {
        $preview = CashierOrderSettings::preview(
            [
                'format_pattern' => '{prefix}{separator}{padded}',
                'separator' => '-',
                'padding' => 3,
                'show_terminal_in_display' => true,
                'kitchen_same_as_display' => true,
                'system_id_mode' => 'dated_serial',
            ],
            ['seq' => 45, 'prefix' => 'A', 'date' => '20261013']
        );

        $this->assertSame('A-045', $preview['display']);
        $this->assertSame('20261013-045', $preview['system']);
        $this->assertSame('A-045', $preview['kitchen']);
    }

    public function test_preview_drops_empty_prefix_separator(): void
    {
        $preview = CashierOrderSettings::preview(
            [
                'format_pattern' => '{prefix}{separator}{padded}',
                'separator' => '-',
                'padding' => 3,
                'global_prefix' => '',
                'show_terminal_in_display' => false,
                'allocation' => 'central',
            ],
            ['seq' => 7, 'prefix' => '']
        );

        $this->assertSame('007', $preview['display']);
    }

    public function test_preview_cycles_display_sequence(): void
    {
        $preview = CashierOrderSettings::preview(
            [
                'cycle_enabled' => true,
                'cycle_max' => 99,
                'start_number' => 1,
                'padding' => 2,
                'format_pattern' => '{padded}',
                'show_terminal_in_display' => false,
                'allocation' => 'central',
            ],
            ['seq' => 101]
        );

        $this->assertSame('02', $preview['display']);
    }

    public function test_normalize_strips_unknown_keys_and_clamps_padding(): void
    {
        $normalized = CashierOrderSettings::normalize([
            'padding' => 99,
            'hack' => 'nope',
            'reset_policy' => 'weekly',
        ]);

        $this->assertSame(8, $normalized['padding']);
        $this->assertSame('daily', $normalized['reset_policy']);
        $this->assertArrayNotHasKey('hack', $normalized);
    }
}
