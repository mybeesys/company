<?php

declare(strict_types=1);

namespace Modules\Establishment\Services;

use Modules\General\Models\Setting;

/**
 * Company-wide cashier order/queue numbering policy.
 * Persistence only — operational sequence generation is not wired yet.
 */
final class CashierOrderSettings
{
    public const KEY = 'cashier_order_settings';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'scope' => 'company',
            'preset' => 'retail',

            'system_id_mode' => 'dated_serial',
            'reset_policy' => 'daily',
            'shift_source' => 'register_shift',

            'start_number' => 1,
            'padding' => 3,
            'cycle_enabled' => false,
            'cycle_max' => 99,
            'cycle_warn_on_reuse' => true,
            'void_recycles_number' => false,

            'allocation' => 'central',
            'prefix_style' => 'letter',
            'global_prefix' => '',
            'separator' => '-',
            'format_pattern' => '{prefix}{separator}{padded}',
            'show_terminal_in_display' => true,

            'show_on_cashier' => true,
            'show_on_receipt' => true,
            'show_on_kds' => true,
            'show_on_queue_screen' => true,
            'kitchen_same_as_display' => true,
            'display_size' => 'xlarge',

            'search_by_display' => true,
            'search_requires_date_when_cycled' => true,

            'offline_policy' => 'terminal_prefix',

            'apply_dining_prefixes' => false,
            'dining_type_prefixes' => [],
            'terminals' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        $raw = Setting::query()->where('key', self::KEY)->value('value');
        $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];

        return self::normalize(is_array($decoded) ? $decoded : []);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function save(array $input): array
    {
        $normalized = self::normalize($input);

        Setting::query()->updateOrCreate(
            ['key' => self::KEY],
            ['value' => json_encode($normalized, JSON_UNESCAPED_UNICODE)]
        );

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        $defaults = self::defaults();
        $merged = array_replace($defaults, array_intersect_key($input, $defaults));

        $merged['enabled'] = self::bool($merged['enabled']);
        $merged['scope'] = self::oneOf($merged['scope'], ['company', 'branch'], 'company');
        $merged['preset'] = self::oneOf($merged['preset'], ['retail', 'qsr', 'multi_pos', 'custom'], 'custom');

        $merged['system_id_mode'] = self::oneOf($merged['system_id_mode'], ['internal', 'dated_serial'], 'dated_serial');
        $merged['reset_policy'] = self::oneOf($merged['reset_policy'], ['daily', 'shift', 'never'], 'daily');
        $merged['shift_source'] = self::oneOf($merged['shift_source'], ['register_shift', 'employee_schedule'], 'register_shift');

        $merged['start_number'] = self::intRange($merged['start_number'], 0, 999999, 1);
        $merged['padding'] = self::intRange($merged['padding'], 1, 8, 3);
        $merged['cycle_enabled'] = self::bool($merged['cycle_enabled']);
        $merged['cycle_max'] = self::intRange($merged['cycle_max'], 1, 999999, 99);
        if ($merged['cycle_max'] < $merged['start_number']) {
            $merged['cycle_max'] = max($merged['start_number'], 99);
        }
        $merged['cycle_warn_on_reuse'] = self::bool($merged['cycle_warn_on_reuse']);
        $merged['void_recycles_number'] = self::bool($merged['void_recycles_number']);

        $merged['allocation'] = self::oneOf($merged['allocation'], ['central', 'prefix', 'range'], 'central');
        $merged['prefix_style'] = self::oneOf($merged['prefix_style'], ['letter', 'numeric', 'ref', 'custom'], 'letter');
        $merged['global_prefix'] = self::prefix((string) $merged['global_prefix']);
        $separator = (string) $merged['separator'];
        $merged['separator'] = in_array($separator, ['-', '/', '.', ' ', ''], true) ? $separator : '-';
        $pattern = trim((string) $merged['format_pattern']);
        $merged['format_pattern'] = $pattern !== '' ? mb_substr($pattern, 0, 80) : '{prefix}{separator}{padded}';
        $merged['show_terminal_in_display'] = self::bool($merged['show_terminal_in_display']);

        foreach (['show_on_cashier', 'show_on_receipt', 'show_on_kds', 'show_on_queue_screen', 'kitchen_same_as_display'] as $flag) {
            $merged[$flag] = self::bool($merged[$flag]);
        }
        $merged['display_size'] = self::oneOf($merged['display_size'], ['normal', 'large', 'xlarge'], 'xlarge');

        $merged['search_by_display'] = self::bool($merged['search_by_display']);
        $merged['search_requires_date_when_cycled'] = self::bool($merged['search_requires_date_when_cycled']);
        $merged['offline_policy'] = self::oneOf($merged['offline_policy'], ['refuse', 'terminal_prefix', 'local_temp'], 'terminal_prefix');

        $merged['apply_dining_prefixes'] = self::bool($merged['apply_dining_prefixes']);
        $merged['dining_type_prefixes'] = self::normalizeDiningPrefixes($input['dining_type_prefixes'] ?? $merged['dining_type_prefixes']);
        $merged['terminals'] = self::normalizeTerminals($input['terminals'] ?? $merged['terminals']);

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $context
     * @return array{display: string, system: string, kitchen: string}
     */
    public static function preview(array $settings, array $context = []): array
    {
        $settings = self::normalize($settings);
        $seq = max(0, (int) ($context['seq'] ?? 45));
        if ($settings['cycle_enabled'] && $settings['cycle_max'] > 0) {
            $span = $settings['cycle_max'] - $settings['start_number'] + 1;
            if ($span > 0) {
                $seq = $settings['start_number'] + (($seq - $settings['start_number']) % $span);
                if ($seq < $settings['start_number']) {
                    $seq = $settings['start_number'];
                }
            }
        }

        $padded = str_pad((string) $seq, (int) $settings['padding'], '0', STR_PAD_LEFT);
        $prefix = self::resolvePreviewPrefix($settings, $context);
        $dining = $settings['apply_dining_prefixes']
            ? self::prefix((string) ($context['dining_prefix'] ?? ''))
            : '';
        $date = (string) ($context['date'] ?? date('Ymd'));
        $shift = (string) ($context['shift'] ?? 'S1');
        $separator = (string) $settings['separator'];

        $display = strtr((string) $settings['format_pattern'], [
            '{prefix}' => $prefix,
            '{dining}' => $dining,
            '{padded}' => $padded,
            '{seq}' => (string) $seq,
            '{date}' => $date,
            '{shift}' => $settings['reset_policy'] === 'shift' ? $shift : '',
            '{separator}' => $separator,
        ]);
        $display = self::collapseSeparators($display, $separator);

        $system = $settings['system_id_mode'] === 'dated_serial'
            ? $date.'-'.$padded
            : '#'.$padded;

        $kitchen = $settings['kitchen_same_as_display'] ? $display : 'K-'.$padded;

        return [
            'display' => $display !== '' ? $display : $padded,
            'system' => $system,
            'kitchen' => $kitchen,
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $context
     */
    private static function resolvePreviewPrefix(array $settings, array $context): string
    {
        if (! $settings['show_terminal_in_display'] && $settings['allocation'] === 'central') {
            return self::prefix((string) $settings['global_prefix']);
        }

        $fromContext = self::prefix((string) ($context['prefix'] ?? ''));
        if ($fromContext !== '') {
            return $fromContext;
        }

        return self::prefix((string) $settings['global_prefix']);
    }

    private static function collapseSeparators(string $value, string $separator): string
    {
        $trimmed = trim($value);
        if ($separator === '') {
            return $trimmed;
        }

        $quoted = preg_quote($separator, '/');
        $collapsed = preg_replace('/(?:\s*'.$quoted.'\s*){2,}/u', $separator, $trimmed) ?? $trimmed;

        return trim($collapsed, $separator.' ');
    }

    /**
     * @param  mixed  $value
     * @return array<string, string>
     */
    private static function normalizeDiningPrefixes(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $id => $prefix) {
            $key = (string) (int) $id;
            if ($key === '0') {
                continue;
            }
            $clean = self::prefix((string) $prefix);
            if ($clean !== '') {
                $out[$key] = $clean;
            }
        }

        return $out;
    }

    /**
     * @param  mixed  $value
     * @return array<string, array{enabled: bool, prefix: string, range_from: int, range_to: int}>
     */
    private static function normalizeTerminals(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $id => $row) {
            if (! is_array($row)) {
                continue;
            }
            $key = (string) (int) $id;
            if ($key === '0') {
                continue;
            }
            $from = self::intRange($row['range_from'] ?? 1, 0, 999999, 1);
            $to = self::intRange($row['range_to'] ?? 500, 1, 999999, 500);
            if ($to < $from) {
                $to = $from;
            }
            $out[$key] = [
                'enabled' => self::bool($row['enabled'] ?? true),
                'prefix' => self::prefix((string) ($row['prefix'] ?? '')),
                'range_from' => $from,
                'range_to' => $to,
            ];
        }

        return $out;
    }

    private static function prefix(string $value): string
    {
        $clean = preg_replace('/[^\p{L}\p{N}]/u', '', trim($value)) ?? '';

        return mb_substr($clean, 0, 8);
    }

    private static function bool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL) === true
            || $value === 1
            || $value === '1'
            || $value === 'on';
    }

    /**
     * @param  list<string>  $allowed
     */
    private static function oneOf(mixed $value, array $allowed, string $fallback): string
    {
        $value = is_string($value) ? $value : (string) $value;

        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function intRange(mixed $value, int $min, int $max, int $fallback): int
    {
        if (! is_numeric($value)) {
            return $fallback;
        }
        $int = (int) $value;

        return max($min, min($max, $int));
    }
}
