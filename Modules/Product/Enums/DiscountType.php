<?php
namespace Modules\Product\Enums;

enum DiscountType: string
{
    case amount = '0';
    case altPrice = '1';
    case percent = '2';
    case rePrice = '3';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function labels(): array
    {
        return array_map(fn($case) => $case->name, self::cases());
    }

    public static function all(): array
    {
        return array_map(
            fn($case) => ['name' => $case->name, 'value' => $case->value],
            self::cases()
        );
    }
}
?>