<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$path = $argv[1] ?? 'C:/Users/ASUS/Downloads/trial-balance-20260831-121529.xlsx';
if (! is_file($path)) {
    fwrite(STDERR, "File not found: {$path}\n");
    exit(1);
}

$spreadsheet = IOFactory::load($path);
$sheet = $spreadsheet->getActiveSheet();
$highestRow = (int) $sheet->getHighestRow();
$highestCol = $sheet->getHighestColumn();

$headerRow = null;
$headers = [];
for ($r = 1; $r <= min(10, $highestRow); $r++) {
    $a1 = trim((string) $sheet->getCell('A'.$r)->getValue());
    $b1 = trim((string) $sheet->getCell('B'.$r)->getValue());
    if ($b1 === 'الاسم' || strtolower($b1) === 'name' || str_contains($b1, 'name')) {
        $headerRow = $r;
        break;
    }
    if (preg_match('/^\d+$/', $a1) && $b1 !== '') {
        $headerRow = max(1, $r - 1);
        break;
    }
}

if ($headerRow === null) {
    $headerRow = 3;
}

$metaRows = [];
for ($r = 1; $r < $headerRow; $r++) {
    $metaRows[] = trim((string) $sheet->getCell('A'.$r)->getValue());
}

function isYellowFill(?Fill $fill): bool
{
    if ($fill === null || $fill->getFillType() === Fill::FILL_NONE) {
        return false;
    }
    $rgb = strtoupper((string) $fill->getStartColor()->getRGB());
    if ($rgb === '' || $rgb === '000000') {
        return false;
    }
    // common yellows: FFFF00, FFF2CC, FFFFE0, FFD966, FFFE00, FFF000
    if (in_array($rgb, ['FFFF00', 'FFF000', 'FFFE00', 'FFFFFF00'], true)) {
        return true;
    }
    if (strlen($rgb) === 6) {
        $r = hexdec(substr($rgb, 0, 2));
        $g = hexdec(substr($rgb, 2, 2));
        $b = hexdec(substr($rgb, 4, 2));

        return $r >= 220 && $g >= 220 && $b <= 180;
    }

    return false;
}

function cellNumeric($sheet, string $coord): float
{
    $v = $sheet->getCell($coord)->getCalculatedValue();
    if ($v === null || $v === '') {
        return 0.0;
    }

    return (float) str_replace(',', '', (string) $v);
}

$allRows = [];
$yellowRows = [];

for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
    $gl = trim((string) $sheet->getCell('A'.$r)->getValue());
    $name = trim((string) $sheet->getCell('B'.$r)->getValue());
    if ($gl === '' && $name === '') {
        continue;
    }
    if (str_contains(mb_strtolower($name), 'total') || str_contains($name, 'المجموع') || str_contains($name, 'إجمال')) {
        continue;
    }

    $row = [
        'row' => $r,
        'gl_code' => $gl,
        'name' => $name,
        'debit_open' => cellNumeric($sheet, 'C'.$r),
        'credit_open' => cellNumeric($sheet, 'D'.$r),
        'debit_period' => cellNumeric($sheet, 'E'.$r),
        'credit_period' => cellNumeric($sheet, 'F'.$r),
        'period_net' => cellNumeric($sheet, 'G'.$r),
        'debit_close' => cellNumeric($sheet, 'H'.$r),
        'credit_close' => cellNumeric($sheet, 'I'.$r),
    ];
    $row['closing_net'] = round($row['debit_close'] - $row['credit_close'], 2);
    $row['period_calc_net'] = round($row['debit_period'] - $row['credit_period'], 2);

    $yellowCols = [];
    foreach (range('A', 'I') as $col) {
        $style = $sheet->getStyle($col.$r);
        if (isYellowFill($style->getFill())) {
            $yellowCols[] = $col;
        }
    }
    $row['yellow_cols'] = $yellowCols;
    $row['is_yellow'] = $yellowCols !== [];

    $allRows[] = $row;
    if ($row['is_yellow']) {
        $yellowRows[] = $row;
    }
}

$sumDebitPeriod = array_sum(array_column($allRows, 'debit_period'));
$sumCreditPeriod = array_sum(array_column($allRows, 'credit_period'));
$sumDebitOpen = array_sum(array_column($allRows, 'debit_open'));
$sumCreditOpen = array_sum(array_column($allRows, 'credit_open'));

echo json_encode([
    'file' => $path,
    'meta' => $metaRows,
    'header_row' => $headerRow,
    'total_data_rows' => count($allRows),
    'yellow_rows_count' => count($yellowRows),
    'sums' => [
        'debit_open' => round($sumDebitOpen, 2),
        'credit_open' => round($sumCreditOpen, 2),
        'debit_period' => round($sumDebitPeriod, 2),
        'credit_period' => round($sumCreditPeriod, 2),
        'period_diff' => round($sumDebitPeriod - $sumCreditPeriod, 2),
    ],
    'yellow_rows' => array_slice($yellowRows, 0, 100),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
