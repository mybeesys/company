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

    public static function isInternalConsumption(object|string|null $transactionOrPurpose): bool
    {
        if (is_string($transactionOrPurpose) || $transactionOrPurpose === null) {
            return self::normalize($transactionOrPurpose) === self::INTERNAL_CONSUMPTION;
        }

        $purpose = $transactionOrPurpose->purpose ?? self::STANDARD;

        return self::normalize(is_string($purpose) ? $purpose : null) === self::INTERNAL_CONSUMPTION;
    }
}
