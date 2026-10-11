<?php

declare(strict_types=1);

namespace Modules\General\Support;

/**
 * SAR money: 2 decimal places (halalas). Parses locale separators so 33,91 and 1,234.56
 * never drop or invent fils.
 */
final class Money
{
    public static function parse(mixed $value): float
    {
        if ($value === null || $value === '' || $value === false) {
            return 0.0;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $s = trim((string) $value);
        if ($s === '' || $s === '-' || $s === '.' || $s === ',') {
            return 0.0;
        }

        $s = strtr($s, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٫' => '.', '٬' => ',',
        ]);
        $s = str_replace(["\u{00A0}", ' ', "\u{202F}"], '', $s);

        $lastComma = strrpos($s, ',');
        $lastDot = strrpos($s, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } elseif ($lastComma !== false) {
            $after = strlen($s) - $lastComma - 1;
            if ($after === 3 && preg_match('/^\d{1,3}(,\d{3})+$/', $s)) {
                $s = str_replace(',', '', $s);
            } else {
                $s = str_replace(',', '.', $s);
            }
        }

        if (! is_numeric($s)) {
            return 0.0;
        }

        return (float) $s;
    }

    public static function round(mixed $value): float
    {
        return round(self::parse($value), 2, PHP_ROUND_HALF_UP);
    }

    public static function halalas(mixed $value): int
    {
        return (int) round(self::parse($value) * 100, 0, PHP_ROUND_HALF_UP);
    }

    public static function fromHalalas(int $halalas): float
    {
        return round($halalas / 100, 2, PHP_ROUND_HALF_UP);
    }

    public static function add(mixed ...$values): float
    {
        $sum = 0;
        foreach ($values as $value) {
            $sum += self::halalas($value);
        }

        return self::fromHalalas($sum);
    }

    public static function sub(mixed $left, mixed $right): float
    {
        return self::fromHalalas(self::halalas($left) - self::halalas($right));
    }

    public static function percent(mixed $base, mixed $rate): float
    {
        return self::round(self::parse($base) * (self::parse($rate) / 100));
    }

    public static function remaining(mixed $total, mixed $paid): float
    {
        $left = self::sub($total, $paid);

        return $left < 0.005 ? 0.0 : $left;
    }
}
