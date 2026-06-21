<?php

namespace Modules\Accounting\Services\JournalEntry;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

class JournalTransactionsExcelParser
{
    /**
     * @return array{
     *     entries: list<array{
     *         ref_no: string,
     *         operation_date: string,
     *         note: string|null,
     *         lines: list<array{gl_code: string, account_name: string, debit: string, credit: string, note: string|null}>
     *     }>,
     *     errors: list<array{ref_no: string|null, row: int, message: string}>
     * }
     */
    public function parse(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('Journal Transactions (2)')
            ?? $spreadsheet->getSheet(0);

        $rows = $sheet->toArray(null, true, true, true);
        $grouped = [];
        $currentRef = null;
        $errors = [];

        foreach ($rows as $rowNum => $row) {
            if ((int) $rowNum === 1) {
                continue;
            }

            $vals = array_values($row);
            $dateRaw = trim((string) ($vals[0] ?? ''));
            $refRaw = trim((string) ($vals[1] ?? ''));
            $accountName = trim((string) ($vals[2] ?? ''));
            $glCode = $this->normalizeGlCode($vals[3] ?? '');
            $description = trim((string) ($vals[4] ?? ''));
            $debitRaw = $vals[6] ?? null;
            $creditRaw = $vals[7] ?? null;

            if ($dateRaw === 'المجموع') {
                continue;
            }

            if ($this->isEntryHeaderRow($dateRaw, $refRaw, $accountName, $glCode)) {
                $currentRef = $this->normalizeRef($dateRaw);

                continue;
            }

            if ($accountName === '' && $glCode === '') {
                continue;
            }

            $refNo = $refRaw !== '' ? $this->normalizeRef($refRaw) : $currentRef;
            if ($refNo === null || $refNo === '') {
                $errors[] = [
                    'ref_no' => null,
                    'row' => (int) $rowNum,
                    'message' => 'missing_ref',
                ];

                continue;
            }

            $debit = $this->normalizeAmount($debitRaw);
            $credit = $this->normalizeAmount($creditRaw);

            if ($debit === '0.00' && $credit === '0.00') {
                continue;
            }

            if ($debit !== '0.00' && $credit !== '0.00') {
                $errors[] = [
                    'ref_no' => $refNo,
                    'row' => (int) $rowNum,
                    'message' => 'both_debit_credit',
                ];

                continue;
            }

            if ($glCode === '') {
                $errors[] = [
                    'ref_no' => $refNo,
                    'row' => (int) $rowNum,
                    'message' => 'missing_gl_code',
                ];

                continue;
            }

            $operationDate = $this->parseDate($dateRaw);
            if ($operationDate === null) {
                $errors[] = [
                    'ref_no' => $refNo,
                    'row' => (int) $rowNum,
                    'message' => 'invalid_date',
                ];

                continue;
            }

            if (! isset($grouped[$refNo])) {
                $grouped[$refNo] = [
                    'ref_no' => $refNo,
                    'operation_date' => $operationDate,
                    'note' => $description !== '' ? $description : null,
                    'lines' => [],
                ];
            }

            if ($grouped[$refNo]['note'] === null && $description !== '') {
                $grouped[$refNo]['note'] = $description;
            }

            $grouped[$refNo]['lines'][] = [
                'gl_code' => $glCode,
                'account_name' => $accountName,
                'debit' => $debit,
                'credit' => $credit,
                'note' => $description !== '' ? $description : null,
            ];
        }

        $entries = [];
        foreach ($grouped as $refNo => $entry) {
            $validationError = $this->validateEntryBalance($entry);
            if ($validationError !== null) {
                $errors[] = [
                    'ref_no' => $refNo,
                    'row' => 0,
                    'message' => $validationError,
                ];

                continue;
            }

            if (count($entry['lines']) < 2) {
                $errors[] = [
                    'ref_no' => $refNo,
                    'row' => 0,
                    'message' => 'too_few_lines',
                ];

                continue;
            }

            $entries[] = $entry;
        }

        usort($entries, fn (array $a, array $b) => strcmp($a['operation_date'].$a['ref_no'], $b['operation_date'].$b['ref_no']));

        return [
            'entries' => $entries,
            'errors' => $errors,
        ];
    }

    private function isEntryHeaderRow(string $dateRaw, string $refRaw, string $accountName, string $glCode): bool
    {
        return $refRaw === ''
            && $accountName === ''
            && $glCode === ''
            && $dateRaw !== ''
            && preg_match('/^\d+$/', $dateRaw) === 1;
    }

    private function normalizeRef(string $raw): string
    {
        $ref = trim($raw);
        if ($ref === '') {
            return '';
        }

        $ref = ltrim($ref, '0');

        return $ref !== '' ? $ref : '0';
    }

    private function normalizeGlCode(mixed $raw): string
    {
        $s = trim((string) $raw);
        if ($s === '') {
            return '';
        }

        $s = preg_replace('/[\x{FEFF}"\s]/u', '', $s) ?? $s;
        $s = str_replace(',', '', $s);

        if (preg_match('/^(\d+)\.0+$/', $s, $m)) {
            $s = $m[1];
        }

        return $s;
    }

    private function normalizeAmount(mixed $raw): string
    {
        if ($raw === null || $raw === '') {
            return '0.00';
        }

        $value = str_replace(',', '', trim((string) $raw));
        if ($value === '' || ! is_numeric($value)) {
            return '0.00';
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function parseDate(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '' || preg_match('/^\d+$/', $raw) === 1) {
            return null;
        }

        $raw = str_replace('\\/', '/', $raw);

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $raw)->format('Y-m-d');
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array{lines: list<array{debit: string, credit: string}>}  $entry
     */
    private function validateEntryBalance(array $entry): ?string
    {
        $debitTotal = '0.00';
        $creditTotal = '0.00';

        foreach ($entry['lines'] as $line) {
            $debitTotal = $this->addDecimal($debitTotal, $line['debit']);
            $creditTotal = $this->addDecimal($creditTotal, $line['credit']);
        }

        if ($this->compareDecimal($debitTotal, $creditTotal) !== 0) {
            return 'unbalanced';
        }

        return null;
    }

    private function addDecimal(string $left, string $right): string
    {
        if (function_exists('bcadd')) {
            return bcadd($left, $right, 2);
        }

        return number_format(((float) $left) + ((float) $right), 2, '.', '');
    }

    private function compareDecimal(string $left, string $right): int
    {
        if (function_exists('bccomp')) {
            return bccomp($left, $right, 2);
        }

        $diff = round(((float) $left) - ((float) $right), 2);

        return $diff < 0 ? -1 : ($diff > 0 ? 1 : 0);
    }
}
