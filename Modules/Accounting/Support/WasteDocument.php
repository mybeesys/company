<?php

declare(strict_types=1);

namespace Modules\Accounting\Support;

use Modules\Establishment\Models\EstablishmentInternalConsumptionType;
use Modules\Sales\Support\TransactionPurpose;

/**
 * Inventory write-off (إتلاف) vs staff-meal internal consumption.
 * Waste always posts Dr waste_expense / Cr inventory (perpetual) or purchases (periodic).
 */
final class WasteDocument
{
    public const JOURNAL_SUB_TYPE = 'waste';

    /** @return list<string> */
    public static function purposeAliases(): array
    {
        return ['waste', 'spoilage', 'inventory_waste'];
    }

    /** @return list<string> */
    public static function typeKeyNeedles(): array
    {
        return [
            'waste',
            'spoilage',
            'inventory_waste',
            'production_waste',
            'إتلاف',
            'اتلاف',
            'هدر',
            'هالك',
        ];
    }

    public static function isWaste(object|string|null $transactionOrPurpose): bool
    {
        if ($transactionOrPurpose === null) {
            return false;
        }

        if (is_string($transactionOrPurpose)) {
            return in_array(strtolower(trim($transactionOrPurpose)), self::purposeAliases(), true);
        }

        $type = strtoupper((string) ($transactionOrPurpose->type ?? ''));
        if ($type === 'WASTE') {
            return true;
        }

        $purpose = strtolower(trim((string) ($transactionOrPurpose->purpose ?? '')));
        if (in_array($purpose, self::purposeAliases(), true)) {
            return true;
        }

        $typeId = (int) ($transactionOrPurpose->internal_consumption_type_id ?? 0);
        if ($typeId <= 0) {
            return false;
        }

        $ic = EstablishmentInternalConsumptionType::query()->find($typeId);

        return $ic ? self::consumptionTypeLooksLikeWaste($ic) : false;
    }

    public static function consumptionTypeLooksLikeWaste(object $type): bool
    {
        $key = strtolower(trim((string) ($type->type_key ?? '')));
        foreach (self::typeKeyNeedles() as $needle) {
            $needle = strtolower($needle);
            if ($key === $needle || ($needle !== '' && str_contains($key, $needle))) {
                return true;
            }
        }

        $en = strtolower((string) ($type->name_en ?? ''));
        $ar = (string) ($type->name_ar ?? '');
        if (str_contains($en, 'waste') || str_contains($en, 'spoil')) {
            return true;
        }

        return str_contains($ar, 'إتلاف')
            || str_contains($ar, 'اتلاف')
            || str_contains($ar, 'هدر')
            || str_contains($ar, 'هالك')
            || str_contains($ar, 'تالف');
    }

    public static function isExpenseLikePosDocument(object $transaction): bool
    {
        return self::isWaste($transaction)
            || TransactionPurpose::isInternalConsumption($transaction);
    }
}
