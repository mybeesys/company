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
}
