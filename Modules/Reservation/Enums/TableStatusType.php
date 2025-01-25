<?php
namespace Modules\Reservation\Enums;

enum TableStatusType: string
{
    case available = '0';
    case reserved = '1';
    case notAvailable = '2';

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