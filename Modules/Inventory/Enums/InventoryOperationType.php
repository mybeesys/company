<?php
namespace Modules\Inventory\Enums;

enum InventoryOperationType: string
{
    case po = '0';
    case prep = '1';
    case rma = '2';
    case waste = '3';
    case transfer = '4';

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