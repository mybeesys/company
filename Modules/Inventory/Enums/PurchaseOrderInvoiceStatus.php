<?php
namespace Modules\Inventory\Enums;

enum PurchaseOrderInvoiceStatus: string
{
    case unIvoiced = '0';
    case partiallyIvoiced = '1';
    case invoiced = '2';

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