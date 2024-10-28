<?php
namespace Modules\Product\Enums;

enum ServiceFeeAutoApplyType: string
{
    case dining = '0';
    case guestCount = '1';
    case paymentType = '2';
    case timeSlot = '3';

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