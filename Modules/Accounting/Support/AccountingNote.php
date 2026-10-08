<?php

namespace Modules\Accounting\Support;

final class AccountingNote
{
    /**
     * Empty / placeholder values → null (do not persist noise in DB).
     */
    public static function normalizeForStorage(mixed $note): ?string
    {
        $t = trim((string) $note);
        if ($t === '' || $t === '—' || $t === '-' || $t === '--') {
            return null;
        }

        return $t;
    }

    /**
     * Line note with optional mapping-level fallback (display / export only).
     */
    public static function resolveForDisplay(
        mixed $lineNote,
        mixed $mappingNote = null,
        bool $placeholderIfEmpty = false
    ): string {
        $text = self::normalizeForStorage($lineNote);
        if ($text === null && $mappingNote !== null) {
            $text = self::normalizeForStorage($mappingNote);
        }

        if ($text === null) {
            return $placeholderIfEmpty ? '—' : '';
        }

        return $text;
    }

    /**
     * Auto journal narration: type — detail — document [#id]
     * Never prefix with «قيد» / «Journal».
     */
    public static function compose(
        string $typeLabel,
        ?string $detail = null,
        ?string $documentRef = null,
        ?int $itemId = null
    ): string {
        $chunks = [];
        $type = self::withoutLeadingJournalWord($typeLabel);
        if ($type !== '') {
            $chunks[] = $type;
        }

        $detail = trim((string) $detail);
        if ($detail !== '' && $detail !== $type) {
            $chunks[] = $detail;
        }

        $documentRef = trim((string) $documentRef);
        if ($documentRef !== '' && $documentRef !== $type && $documentRef !== $detail) {
            $chunks[] = $documentRef;
        }

        $note = implode(' — ', $chunks);
        if ($itemId !== null && $itemId > 0) {
            $note = trim($note.' [#'.$itemId.']');
        }

        return $note !== '' ? $note : $typeLabel;
    }

    public static function forTransactionType(?string $type, mixed $transactionOrRef = null): string
    {
        $ar = app()->getLocale() === 'ar';
        $label = match ((string) $type) {
            'sell' => $ar ? 'مبيعات' : 'Sales',
            'purchases', 'purchase' => $ar ? 'مشتريات' : 'Purchases',
            'sell-return' => $ar ? 'مردود مبيعات' : 'Sales return',
            'purchases-return' => $ar ? 'مردود مشتريات' : 'Purchase return',
            'receipt_voucher' => $ar ? 'سند قبض' : 'Receipt voucher',
            'payment_voucher' => $ar ? 'سند صرف' : 'Payment voucher',
            default => $type !== null && $type !== ''
                ? (string) $type
                : ($ar ? 'حركة' : 'Entry'),
        };

        return self::compose($label, null, self::documentRef($transactionOrRef));
    }

    public static function forSettlement(?string $transactionType, mixed $transactionOrRef = null): string
    {
        $ar = app()->getLocale() === 'ar';
        $isPurchase = in_array((string) $transactionType, ['purchases', 'purchase', 'purchases-return'], true);
        $label = $isPurchase
            ? ($ar ? 'سند صرف' : 'Payment voucher')
            : ($ar ? 'سند قبض' : 'Receipt voucher');

        return self::compose($label, null, self::documentRef($transactionOrRef));
    }

    public static function documentRef(mixed $transactionOrRef): string
    {
        if (is_object($transactionOrRef)) {
            return trim((string) ($transactionOrRef->ref_no ?? $transactionOrRef->invoice_no ?? $transactionOrRef->id ?? ''));
        }

        return trim((string) $transactionOrRef);
    }

    public static function withoutLeadingJournalWord(string $label): string
    {
        $label = trim($label);
        if ($label === '') {
            return '';
        }

        $stripped = preg_replace('/^(قيد|Journal entry|Journal|Entry)\s+/iu', '', $label);

        return trim((string) $stripped);
    }
}
