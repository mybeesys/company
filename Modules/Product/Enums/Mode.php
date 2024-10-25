<?php
namespace Modules\Product\Enums;

enum Mode: string
{
    case bar = '0';
    case kiosk = '1';
    case menuBoard = '2';
    case multiChannel = '3';
    case online = '4';
    case catering = '5';
    case smartDining = '6';

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