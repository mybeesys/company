<?php

namespace Modules\Accounting\Support;

final class CoaColorSystem
{
    /**
     * Blend toward the workbook's L3 wash so icons stay in-family but airy.
     */
    private const UI_LIFT = 0.32;

    /**
     * @return array{family: string, level: int, accent: string, background: string, color: string, class: string}
     */
    public static function resolve(?string $primaryType, int $level): array
    {
        $family = match ($primaryType) {
            'liabilities' => 'liabilities',
            'equity' => 'equity',
            'income' => 'income',
            'expenses' => 'expenses',
            default => 'asset',
        };

        $palette = MyBeeMasterCoaRules::colorSystem()[$family] ?? MyBeeMasterCoaRules::colorSystem()['asset'];
        $level = max(1, min($level, count($palette)));
        $background = $palette[$level - 1];
        $text = self::needsLightText($background) ? '#ffffff' : '#1f2937';

        return [
            'family' => $family,
            'level' => $level,
            'accent' => self::uiAccentFromPalette($palette),
            'background' => $background,
            'color' => $text,
            'class' => 'coa-tone coa-tone-'.$family.'-l'.$level,
        ];
    }

    public static function toneClass(?string $primaryType, int $level): string
    {
        return self::resolve($primaryType, $level)['class'];
    }

    public static function needsLightText(string $hex): bool
    {
        $rgb = self::hexToRgb($hex);
        if ($rgb === null) {
            return false;
        }

        $luminance = (0.299 * $rgb[0] + 0.587 * $rgb[1] + 0.114 * $rgb[2]) / 255;

        return $luminance < 0.55;
    }

    /**
     * @param  list<string>  $palette
     */
    public static function uiAccentFromPalette(array $palette): string
    {
        $mid = $palette[min(1, count($palette) - 1)];
        $air = $palette[min(2, count($palette) - 1)] ?? $mid;

        if (strcasecmp($mid, $air) === 0) {
            return self::mixHex($mid, '#FFFFFF', 0.22);
        }

        return self::mixHex($mid, $air, self::UI_LIFT);
    }

    public static function mixHex(string $from, string $to, float $amount): string
    {
        $start = self::hexToRgb($from);
        $end = self::hexToRgb($to);
        if ($start === null || $end === null) {
            return $from;
        }

        $t = max(0.0, min(1.0, $amount));
        $r = (int) round($start[0] + ($end[0] - $start[0]) * $t);
        $g = (int) round($start[1] + ($end[1] - $start[1]) * $t);
        $b = (int) round($start[2] + ($end[2] - $start[2]) * $t);

        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    /** @return array{0: int, 1: int, 2: int}|null */
    private static function hexToRgb(string $hex): ?array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return null;
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
