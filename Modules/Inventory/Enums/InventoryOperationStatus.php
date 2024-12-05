<?php
namespace Modules\Inventory\Enums;

enum InventoryOperationStatus: string
{
    case new_op = '0';
    case sent = '1';
    case partiallyReceived = '2';
    case fullyReceived = '3';
    case finalized = '4';
    case preped = '5';
    case approved = '6';

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