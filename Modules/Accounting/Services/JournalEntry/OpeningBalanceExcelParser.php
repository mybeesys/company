<?php

namespace Modules\Accounting\Services\JournalEntry;

use PhpOffice\PhpSpreadsheet\IOFactory;

class OpeningBalanceExcelParser
{
    /**
     * @return array{
     *     lines: list<array{gl_code: string, account_name: string, debit: string, credit: string}>,
     *     debit_total: string,
     *     credit_total: string,
     *     errors: list<array{row: int, message: string}>
     * }
     */
    public function parse(string $filePath): array
    {
        @ini_set('memory_limit', '512M');

        $reader = IOFactory::createReaderForFile($filePath);
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        $sheet = $reader->load($filePath)->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $lines = [];
        $errors = [];
        $dataStartRow = $this->detectDataStartRow($rows);

        foreach ($rows as $rowNum => $row) {
            if ((int) $rowNum < $dataStartRow) {
                continue;
            }

            $vals = array_values($row);
            $accountName = $this->cleanText($vals[0] ?? '');
            $glCode = $this->normalizeGlCode($vals[1] ?? '');
            $debit = $this->normalizeAmount($vals[2] ?? null);
            $credit = $this->normalizeAmount($vals[3] ?? null);

            if ($this->isTotalRow($accountName, $glCode)) {
                continue;
            }

            if ($glCode === '') {
                if ($accountName !== '') {
                    $errors[] = ['row' => (int) $rowNum, 'message' => 'missing_gl_code'];
                }

                continue;
            }

            if ($debit === '0.00' && $credit === '0.00') {
                continue;
            }

            if ($debit !== '0.00' && $credit !== '0.00') {
                $errors[] = ['row' => (int) $rowNum, 'message' => 'both_debit_credit'];

                continue;
            }

            $lines[] = [
                'gl_code' => $glCode,
                'account_name' => $accountName,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        $debitTotal = '0.00';
        $creditTotal = '0.00';
        foreach ($lines as $line) {
            $debitTotal = $this->addDecimal($debitTotal, $line['debit']);
            $creditTotal = $this->addDecimal($creditTotal, $line['credit']);
        }

        if ($lines !== [] && abs((float) $debitTotal - (float) $creditTotal) > 1.0) {
            $errors[] = ['row' => 0, 'message' => 'unbalanced'];
        }

        if (count($lines) < 2 && $errors === []) {
            $errors[] = ['row' => 0, 'message' => 'too_few_lines'];
        }

        return [
            'lines' => $lines,
            'debit_total' => $debitTotal,
            'credit_total' => $creditTotal,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $rows
     */
    private function detectDataStartRow(array $rows): int
    {
        foreach ($rows as $rowNum => $row) {
            $vals = array_map(fn ($v) => $this->cleanText($v), array_values($row));
            $joined = implode(' ', $vals);

            if (preg_match('/مدين|debit/i', $joined) && preg_match('/دائن|credit/i', $joined)) {
                return (int) $rowNum + 1;
            }

            if (preg_match('/كود|code|gl/i', $joined) && preg_match('/اسم|name/i', $joined)) {
                return (int) $rowNum + 1;
            }
        }

        return 3;
    }

    private function isTotalRow(string $accountName, string $glCode): bool
    {
        if ($glCode !== '') {
            return false;
        }

        $name = mb_strtolower($accountName);

        return str_contains($name, 'إجمالي')
            || str_contains($name, 'total')
            || str_contains($name, 'المجموع');
    }

    private function cleanText(mixed $raw): string
    {
        $s = trim((string) $raw);

        return preg_replace('/[\x{FEFF}]/u', '', $s) ?? $s;
    }

    private function normalizeGlCode(mixed $raw): string
    {
        $s = $this->cleanText($raw);
        if ($s === '') {
            return '';
        }

        $s = preg_replace('/[\s"]/u', '', $s) ?? $s;
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
