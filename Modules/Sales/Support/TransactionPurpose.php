<?php

declare(strict_types=1);

namespace Modules\Sales\Support;

/**
 * Business purpose of a sell-like POS document.
 * standard = normal sales invoice; internal_consumption = stock out as expense (e.g. staff meals).
 */
final class TransactionPurpose
{
    public const STANDARD = 'standard';

    public const INTERNAL_CONSUMPTION = 'internal_consumption';

    /** @deprecated alias accepted from mobile */
    public const STAFF_MEALS = 'staff_meals';

    public const JOURNAL_SUB_TYPE = 'internal_consumption';

    /** @return list<string> */
    public static function internalAliases(): array
    {
        return [self::INTERNAL_CONSUMPTION, self::STAFF_MEALS];
    }

    public static function normalize(?string $purpose): string
    {
        $value = strtolower(trim((string) $purpose));
        if ($value === '' || $value === self::STANDARD) {
            return self::STANDARD;
        }

        if (in_array($value, self::internalAliases(), true)) {
            return self::INTERNAL_CONSUMPTION;
        }

        return self::STANDARD;
    }

    public static function isRequested(mixed $purpose, mixed $typeId = null): bool
    {
        $purposeStr = is_string($purpose) || $purpose === null ? $purpose : (string) $purpose;
        if (self::normalize($purposeStr) === self::INTERNAL_CONSUMPTION) {
            return true;
        }

        return (int) $typeId > 0;
    }

    public static function isInternalConsumption(object|string|null $transactionOrPurpose): bool
    {
        if (is_string($transactionOrPurpose) || $transactionOrPurpose === null) {
            return self::normalize($transactionOrPurpose) === self::INTERNAL_CONSUMPTION;
        }

        $purpose = $transactionOrPurpose->purpose ?? self::STANDARD;
        if (self::normalize(is_string($purpose) ? $purpose : null) === self::INTERNAL_CONSUMPTION) {
            return true;
        }

        return (int) ($transactionOrPurpose->internal_consumption_type_id ?? 0) > 0;
    }
}
