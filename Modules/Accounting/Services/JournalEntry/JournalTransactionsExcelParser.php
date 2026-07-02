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
        $rows = $this->loadRows($filePath);
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

            $debit = $this->normalizeAmount($debitRaw);
            $credit = $this->normalizeAmount($creditRaw);

            if ($this->isSubtotalRow($dateRaw, $accountName, $glCode, $debit, $credit)) {
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

            $debitRawValue = $this->parseAmountRaw($debitRaw);
            $creditRawValue = $this->parseAmountRaw($creditRaw);

            if ($debitRawValue == 0.0 && $creditRawValue == 0.0) {
                continue;
            }

            if ($debitRawValue > 0.0 && $creditRawValue > 0.0) {
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
                'debit_raw' => $debitRawValue,
                'credit_raw' => $creditRawValue,
                'note' => $description !== '' ? $description : null,
            ];
        }

        $entries = [];
        foreach ($grouped as $refNo => $entry) {
            $entry = $this->normalizeAndBalanceEntry($entry);
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

    private function isSubtotalRow(string $dateRaw, string $accountName, string $glCode, string $debit, string $credit): bool
    {
        if ($dateRaw === 'المجموع') {
            return true;
        }

        if ($accountName !== '' || $glCode !== '') {
            return false;
        }

        if ($debit === '0.00' || $credit === '0.00') {
            return false;
        }

        return $debit === $credit;
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
        return $this->formatAmount($this->parseAmountRaw($raw));
    }

    private function parseAmountRaw(mixed $raw): float
    {
        if ($raw === null || $raw === '') {
            return 0.0;
        }

        $value = str_replace(',', '', trim((string) $raw));
        if ($value === '' || ! is_numeric($value)) {
            return 0.0;
        }

        return (float) $value;
    }

    private function formatAmount(float $value): string
    {
        return number_format(round($value, 2), 2, '.', '');
    }

    /**
     * @param  array{lines: list<array{gl_code: string, account_name: string, debit_raw: float, credit_raw: float, note: string|null}>}  $entry
     * @return array{lines: list<array{gl_code: string, account_name: string, debit: string, credit: string, note: string|null}>}
     */
    private function normalizeAndBalanceEntry(array $entry): array
    {
        $lines = [];
        foreach ($entry['lines'] as $line) {
            $lines[] = [
                'gl_code' => $line['gl_code'],
                'account_name' => $line['account_name'],
                'debit' => $line['debit_raw'] > 0 ? $this->formatAmount($line['debit_raw']) : '0.00',
                'credit' => $line['credit_raw'] > 0 ? $this->formatAmount($line['credit_raw']) : '0.00',
                'note' => $line['note'],
            ];
        }

        $entry['lines'] = $this->rebalanceNormalizedLines($lines);

        return $entry;
    }

    /**
     * @param  list<array{debit: string, credit: string}>  $lines
     * @return list<array{debit: string, credit: string}>
     */
    private function rebalanceNormalizedLines(array $lines): array
    {
        $debitTotal = '0.00';
        $creditTotal = '0.00';
        foreach ($lines as $line) {
            $debitTotal = $this->addDecimal($debitTotal, $line['debit']);
            $creditTotal = $this->addDecimal($creditTotal, $line['credit']);
        }

        $diff = function_exists('bcsub')
            ? (float) bcsub($creditTotal, $debitTotal, 2)
            : round((float) $creditTotal - (float) $debitTotal, 2);

        if (abs($diff) < 0.00001 || abs($diff) > 0.01) {
            return $lines;
        }

        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if ($diff > 0 && (float) $lines[$i]['debit'] > 0) {
                $lines[$i]['debit'] = $this->formatAmount((float) $lines[$i]['debit'] + $diff);

                return $lines;
            }
            if ($diff < 0 && (float) $lines[$i]['credit'] > 0) {
                $lines[$i]['credit'] = $this->formatAmount((float) $lines[$i]['credit'] + abs($diff));

                return $lines;
            }
        }

        return $lines;
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
     * @param  array{lines: list<array{debit_raw?: float, credit_raw?: float, debit?: string, credit?: string}>}  $entry
     */
    private function validateEntryBalance(array $entry): ?string
    {
        $rawDebit = 0.0;
        $rawCredit = 0.0;

        foreach ($entry['lines'] as $line) {
            if (isset($line['debit_raw'], $line['credit_raw'])) {
                $rawDebit += (float) $line['debit_raw'];
                $rawCredit += (float) $line['credit_raw'];

                continue;
            }

            $rawDebit += (float) ($line['debit'] ?? 0);
            $rawCredit += (float) ($line['credit'] ?? 0);
        }

        if ($this->formatAmount($rawDebit) === $this->formatAmount($rawCredit)) {
            return null;
        }

        $debitTotal = '0.00';
        $creditTotal = '0.00';
        foreach ($entry['lines'] as $line) {
            $debitTotal = $this->addDecimal($debitTotal, (string) ($line['debit'] ?? '0.00'));
            $creditTotal = $this->addDecimal($creditTotal, (string) ($line['credit'] ?? '0.00'));
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

    /**
     * @return array<int|string, array<string, mixed>>
     */
    private function loadRows(string $filePath): array
    {
        @ini_set('memory_limit', '768M');

        $reader = IOFactory::createReaderForFile($filePath);
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        $sheetName = $this->resolveSheetName($reader, $filePath);
        if (method_exists($reader, 'setLoadSheetsOnly')) {
            $reader->setLoadSheetsOnly([$sheetName]);
        }

        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getSheetByName($sheetName) ?? $spreadsheet->getActiveSheet();

        return $sheet->toArray(null, true, true, true);
    }

    private function resolveSheetName(object $reader, string $filePath): string
    {
        $preferred = 'Journal Transactions (2)';

        if (method_exists($reader, 'listWorksheetNames')) {
            $names = $reader->listWorksheetNames($filePath);
            if (in_array($preferred, $names, true)) {
                return $preferred;
            }

            return $names[0] ?? $preferred;
        }

        return $preferred;
    }
}
