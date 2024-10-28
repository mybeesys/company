<?php
namespace Modules\Product\Enums;

enum ServiceFeeType: string
{
    case amount = '0';
    case percentage = '1';

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