<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? 'C:/Users/ASUS/Downloads/trial-balance-20260831-121529.xlsx';
$spreadsheet = IOFactory::load($path);
$sheet = $spreadsheet->getActiveSheet();
$highestRow = (int) $sheet->getHighestRow();

function num($sheet, string $coord): ?float
{
    $v = $sheet->getCell($coord)->getCalculatedValue();
    if ($v === null || $v === '') {
        return null;
    }

    return round((float) str_replace(',', '', (string) $v), 2);
}

function isYellow($sheet, string $coord): bool
{
    $rgb = strtoupper((string) $sheet->getStyle($coord)->getFill()->getStartColor()->getRGB());

    return $rgb === 'FFFF00';
}

// Read header row 3 for all columns
$headers = [];
foreach (range('A', 'O') as $col) {
    $headers[$col] = trim((string) $sheet->getCell($col.'3')->getValue());
}

$diffs = [];
$allCompared = [];

for ($r = 4; $r <= $highestRow; $r++) {
    $gl = trim((string) $sheet->getCell('A'.$r)->getValue());
    $name = trim((string) $sheet->getCell('B'.$r)->getValue());
    if ($gl === '' && $name === '') {
        continue;
    }
    if (str_contains(mb_strtolower($name), 'total') || str_contains($name, 'المجموع')) {
        continue;
    }

    $beeCloseDebit = num($sheet, 'G'.$r) ?? 0;
    $beeCloseCredit = num($sheet, 'H'.$r) ?? 0;
    $beeCloseNet = round($beeCloseDebit - $beeCloseCredit, 2);

    $beeOpenDebit = num($sheet, 'C'.$r) ?? 0;
    $beeOpenCredit = num($sheet, 'D'.$r) ?? 0;
    $beePeriodDebit = num($sheet, 'E'.$r) ?? 0;
    $beePeriodCredit = num($sheet, 'F'.$r) ?? 0;

    // External columns - detect structure from row 3
    $extName = trim((string) $sheet->getCell('J'.$r)->getValue());
    $extK = num($sheet, 'K'.$r);
    $extL = num($sheet, 'L'.$r);
    $extM = num($sheet, 'M'.$r);
    $extN = num($sheet, 'N'.$r);
    $extO = num($sheet, 'O'.$r);

    $yellowO = isYellow($sheet, 'O'.$r);

    // Try to infer external closing balance
    // Common patterns: K=debit close, L=credit close OR K=net close
    $extCloseDebit = $extK;
    $extCloseCredit = $extL;
    $extCloseNet = null;
    if ($extK !== null && $extL !== null) {
        $extCloseNet = round($extK - $extL, 2);
    } elseif ($extK !== null) {
        $extCloseNet = $extK;
    }

    $diffCloseNet = ($extCloseNet !== null) ? round($beeCloseNet - $extCloseNet, 2) : null;
    $diffPeriodDebit = ($extM !== null) ? round($beePeriodDebit - $extM, 2) : null;
    $diffPeriodCredit = ($extN !== null) ? round($beePeriodCredit - $extN, 2) : null;

    $row = [
        'row' => $r,
        'gl_code' => $gl,
        'name_bee' => $name,
        'name_ext' => $extName,
        'bee' => [
            'open_debit' => $beeOpenDebit,
            'open_credit' => $beeOpenCredit,
            'period_debit' => $beePeriodDebit,
            'period_credit' => $beePeriodCredit,
            'close_debit' => $beeCloseDebit,
            'close_credit' => $beeCloseCredit,
            'close_net' => $beeCloseNet,
        ],
        'external' => [
            'K' => $extK,
            'L' => $extL,
            'M' => $extM,
            'N' => $extN,
            'O' => $extO,
            'close_net' => $extCloseNet,
        ],
        'diff' => [
            'close_net' => $diffCloseNet,
            'period_debit' => $diffPeriodDebit,
            'period_credit' => $diffPeriodCredit,
            'O_value' => $extO,
        ],
        'yellow_O' => $yellowO,
    ];

    $allCompared[] = $row;

    $hasDiff = $yellowO
        || ($diffCloseNet !== null && abs($diffCloseNet) >= 0.01)
        || ($extO !== null && abs($extO) >= 0.01);

    if ($hasDiff) {
        $diffs[] = $row;
    }
}

// Sort by absolute diff
usort($diffs, fn ($a, $b) => abs($b['diff']['O_value'] ?? $b['diff']['close_net'] ?? 0) <=> abs($a['diff']['O_value'] ?? $a['diff']['close_net'] ?? 0));

$sumDiffO = array_sum(array_map(fn ($d) => $d['diff']['O_value'] ?? 0, $diffs));
$sumBeePeriodDebit = array_sum(array_column(array_column($allCompared, 'bee'), 'period_debit'));
$sumBeePeriodCredit = array_sum(array_column(array_column($allCompared, 'bee'), 'period_credit'));

echo json_encode([
    'headers' => $headers,
    'total_rows' => count($allCompared),
    'diff_rows' => count($diffs),
    'sum_diff_O' => round($sumDiffO, 2),
    'sample_row_4_raw' => [
        'J' => $sheet->getCell('J4')->getValue(),
        'K' => $sheet->getCell('K4')->getCalculatedValue(),
        'L' => $sheet->getCell('L4')->getCalculatedValue(),
        'M' => $sheet->getCell('M4')->getCalculatedValue(),
        'N' => $sheet->getCell('N4')->getCalculatedValue(),
        'O' => $sheet->getCell('O4')->getCalculatedValue(),
    ],
    'top_diffs' => array_slice($diffs, 0, 50),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
