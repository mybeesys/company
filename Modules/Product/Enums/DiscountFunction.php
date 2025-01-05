<?php
namespace Modules\Product\Enums;

enum DiscountFunction: string
{
    case standard = '0';
    //case customer = '1';
    case giftwithPurchase = '2';
    //case loyalty = '3';

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