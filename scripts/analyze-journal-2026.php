<?php

require __DIR__.'/../vendor/autoload.php';

use Modules\Accounting\Services\JournalEntry\JournalTransactionsExcelParser;

$path = $argv[1] ?? 'C:/Users/ASUS/Downloads/Journal Transactions (1).xls';
$startDate = $argv[2] ?? '2026-01-01';
$endDate = $argv[3] ?? '2026-07-31';

$targetGls = [
    '514', '519', '526', '533', '5210', '5245', '530', '536', '5216', '5221', '522',
    '5251', '524', '5224', '5209', '5202', '5249', '523', '534', '5212',
    '215001', '215002', '2214', '221', '223',
    '32', '36',
    '1202001', '1202002', '12041003', '12075', '2220', '2224',
    '2211015',
];

$parser = new JournalTransactionsExcelParser;
$parsed = $parser->parse($path);
$entries = $parsed['entries'];
$errors = $parsed['errors'];

$inPeriod = array_values(array_filter(
    $entries,
    fn (array $e) => $e['operation_date'] >= $startDate && $e['operation_date'] <= $endDate
));

$glTotals = [];
$glRefs = [];
foreach ($inPeriod as $entry) {
    foreach ($entry['lines'] as $line) {
        $gl = $line['gl_code'];
        if (! in_array($gl, $targetGls, true)) {
            continue;
        }
        $debit = (float) $line['debit'];
        $credit = (float) $line['credit'];
        if (! isset($glTotals[$gl])) {
            $glTotals[$gl] = ['debit' => 0.0, 'credit' => 0.0, 'lines' => 0, 'refs' => []];
        }
        $glTotals[$gl]['debit'] += $debit;
        $glTotals[$gl]['credit'] += $credit;
        $glTotals[$gl]['lines']++;
        $glTotals[$gl]['refs'][$entry['ref_no']] = true;
    }
}

foreach ($glTotals as $gl => &$t) {
    $t['debit'] = round($t['debit'], 2);
    $t['credit'] = round($t['credit'], 2);
    $t['net'] = round($t['debit'] - $t['credit'], 2);
    $t['ref_count'] = count($t['refs']);
    unset($t['refs']);
}
unset($t);

// Expected Bee period from trial balance xlsx
$beeExpected = [
    '514' => ['debit' => 20676673.24, 'credit' => 97154.74],
    '519' => ['debit' => 197085.14, 'credit' => 0],
    '526' => ['debit' => 51849.92, 'credit' => 0],
    '533' => ['debit' => 82004.61, 'credit' => 0],
    '5210' => ['debit' => 1142.74, 'credit' => 0],
    '5245' => ['debit' => 212519, 'credit' => 0],
    '530' => ['debit' => 99313, 'credit' => 0],
    '536' => ['debit' => 0, 'credit' => 0],
    '215001' => ['debit' => 3265622.23, 'credit' => 4552295.07],
    '215002' => ['debit' => 784852.04, 'credit' => 588754.62],
    '2214' => ['debit' => 4003394.05, 'credit' => 3256453.57],
    '2220' => ['debit' => 43500, 'credit' => 0],
    '2224' => ['debit' => 2000418.02, 'credit' => 0],
    '12075' => ['debit' => 1696599.83, 'credit' => 0],
];

$extExpected = [
    '514' => ['debit' => 20717390.24, 'credit' => 97154.74],
    '519' => ['debit' => 225389.14, 'credit' => 0],
    '526' => ['debit' => 64224.92, 'credit' => 0],
    '533' => ['debit' => 89699.61, 'credit' => 0],
    '5210' => ['debit' => 7142.74, 'credit' => 0],
    '5245' => ['debit' => 218519, 'credit' => 0],
    '530' => ['debit' => 103513, 'credit' => 0],
    '536' => ['debit' => 3000, 'credit' => 0],
    '215001' => ['debit' => 3918930.41, 'credit' => 4552295.10],
    '215002' => ['debit' => 688417.18, 'credit' => 588754.62],
    '2214' => ['debit' => 0, 'credit' => 0], // external zero closing movement implied
    '2220' => ['debit' => 71000, 'credit' => 0],
    '2224' => ['debit' => 2027918.02, 'credit' => 0],
    '12075' => ['debit' => 1704086.94, 'credit' => 0],
];

$comparison = [];
foreach ($beeExpected as $gl => $bee) {
    $file = $glTotals[$gl] ?? ['debit' => 0, 'credit' => 0, 'net' => 0, 'lines' => 0, 'ref_count' => 0];
    $ext = $extExpected[$gl] ?? ['debit' => 0, 'credit' => 0];
    $comparison[] = [
        'gl_code' => $gl,
        'file_debit' => $file['debit'],
        'file_credit' => $file['credit'],
        'file_net' => $file['net'],
        'file_refs' => $file['ref_count'],
        'bee_debit' => $bee['debit'],
        'bee_credit' => $bee['credit'],
        'ext_debit' => $ext['debit'],
        'ext_credit' => $ext['credit'],
        'file_vs_bee_debit' => round($file['debit'] - $bee['debit'], 2),
        'file_vs_ext_debit' => round($file['debit'] - $ext['debit'], 2),
        'bee_vs_ext_debit' => round($bee['debit'] - $ext['debit'], 2),
    ];
}

// Unbalanced entries detail
$unbalanced = [];
foreach ($errors as $err) {
    if (($err['message'] ?? '') === 'unbalanced') {
        $unbalanced[] = $err;
    }
}

// Entries outside period
$outside = array_values(array_filter(
    $entries,
    fn (array $e) => $e['operation_date'] < $startDate || $e['operation_date'] > $endDate
));

// Find refs touching target GL with round amounts matching diffs
$diffAmounts = [40717, 28304, 12375, 7695, 6000, 4200, 3000, 2700, 2500, 2425, 27500, 653308.17];
$matchingRefs = [];
foreach ($inPeriod as $entry) {
    foreach ($entry['lines'] as $line) {
        $amt = max((float) $line['debit'], (float) $line['credit']);
        foreach ($diffAmounts as $target) {
            if (abs($amt - $target) < 0.02) {
                $matchingRefs[] = [
                    'ref_no' => $entry['ref_no'],
                    'date' => $entry['operation_date'],
                    'gl_code' => $line['gl_code'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'amount' => round($amt, 2),
                    'matched_diff' => $target,
                ];
            }
        }
    }
}

echo json_encode([
    'file' => $path,
    'period' => [$startDate, $endDate],
    'total_valid_entries' => count($entries),
    'entries_in_period' => count($inPeriod),
    'entries_outside_period' => count($outside),
    'outside_date_range' => $outside !== [] ? [
        'min' => min(array_column($outside, 'operation_date')),
        'max' => max(array_column($outside, 'operation_date')),
    ] : null,
    'parse_errors' => count($errors),
    'unbalanced_entries' => count($unbalanced),
    'unbalanced_sample' => array_slice($unbalanced, 0, 25),
    'gl_totals_in_period' => $glTotals,
    'comparison_file_bee_ext' => $comparison,
    'matching_round_amount_refs' => array_slice($matchingRefs, 0, 40),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
