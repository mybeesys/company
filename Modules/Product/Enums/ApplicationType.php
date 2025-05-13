<?php

namespace Modules\Product\Enums;

enum ApplicationType: string
{
    case Mode = '2';
    case timeSlotOnly = '0';
    case manual = '1';
    case posStation = '3';

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
