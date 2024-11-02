<?php
namespace Modules\Product\Enums;

enum DiscountQualificationType: string
{
    // case class_ = '0';
    // case group = '1';
    case modifier = '2';
    case modifierClass = '3';
    case product = '4';

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