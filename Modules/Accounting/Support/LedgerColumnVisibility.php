<?php

namespace Modules\Accounting\Support;

class LedgerColumnVisibility
{
    /**
     * Parse ledger_cols query string "1,0,1,1,1,1,1" (1 = visible). Index 6 (balance) is always visible.
     *
     * @return array<int, bool> keys 0..6
     */
    public static function parse(?string $ledgerColsQuery): array
    {
        $v = array_fill(0, 7, true);
        if ($ledgerColsQuery === null || trim($ledgerColsQuery) === '') {
            return $v;
        }
        $parts = explode(',', $ledgerColsQuery);
        for ($i = 0; $i < 7; $i++) {
            if (! isset($parts[$i])) {
                continue;
            }
            $p = strtolower(trim((string) $parts[$i]));
            $v[$i] = in_array($p, ['1', 'true', 'yes'], true);
        }
        $v[6] = true;

        return $v;
    }

    /**
     * @param  array<int, bool>  $flags
     */
    public static function toQueryString(array $flags): string
    {
        $flags[6] = true;
        $out = [];
        for ($i = 0; $i < 7; $i++) {
            $out[] = ! empty($flags[$i]) ? '1' : '0';
        }

        return implode(',', $out);
    }

    /**
     * Map column index 0..6 to 0..n-1 visible index, or null if hidden.
     *
     * @param  array<int, bool>  $v
     * @return array<int, int|null>
     */
    public static function visiblePositions(array $v): array
    {
        $pos = [];
        $ci = 0;
        for ($i = 0; $i < 7; $i++) {
            if (! empty($v[$i])) {
                $pos[$i] = $ci++;
            } else {
                $pos[$i] = null;
            }
        }

        return $pos;
    }

    /**
     * Count visible columns in inclusive range.
     *
     * @param  array<int, bool>  $v
     */
    public static function countVisibleInRange(array $v, int $start, int $end): int
    {
        $n = 0;
        for ($i = $start; $i <= $end; $i++) {
            if (! empty($v[$i])) {
                $n++;
            }
        }

        return $n;
    }
}
