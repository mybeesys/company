<?php

require __DIR__.'/../vendor/autoload.php';

use Modules\Accounting\Services\JournalEntry\JournalTransactionsExcelParser;

$path = $argv[1] ?? 'C:/Users/ASUS/Downloads/Journal Transactions (1) (1).xls';
$parser = new JournalTransactionsExcelParser();
$entries = $parser->parse($path)['entries'];

$target = '12050';
$hits = [];
foreach ($entries as $e) {
    $has = false;
    $lines = [];
    $d = 0.0;
    $c = 0.0;
    foreach ($e['lines'] as $l) {
        if ($l['gl_code'] === $target) {
            $has = true;
            $d += (float) $l['debit'];
            $c += (float) $l['credit'];
        }
        $lines[] = [
            'gl' => $l['gl_code'],
            'name' => $l['account_name'],
            'debit' => $l['debit'],
            'credit' => $l['credit'],
            'note' => $l['note'],
        ];
    }
    if (! $has) {
        continue;
    }
    $hits[] = [
        'ref' => $e['ref_no'],
        'date' => $e['operation_date'],
        '12050_debit' => round($d, 2),
        '12050_credit' => round($c, 2),
        'net' => round($d - $c, 2),
        'lines' => $lines,
        'note' => $e['note'],
    ];
}

usort($hits, fn ($a, $b) => abs($b['net']) <=> abs($a['net']));

echo "12050 entries: ".count($hits)."\n";
echo 'Total debit: '.array_sum(array_column($hits, '12050_debit'))."\n";
echo 'Total credit: '.array_sum(array_column($hits, '12050_credit'))."\n";
echo 'Net: '.round(array_sum(array_column($hits, '12050_debit')) - array_sum(array_column($hits, '12050_credit')), 2)."\n\n";

// Look for amount near 98172.85 or components
$needles = [98172.85, 92180, 4062, 1930.85, 50000, 48172.85];
echo "=== Entries with amounts near known diffs ===\n";
foreach ($hits as $h) {
    $interesting = abs($h['net']) >= 1000;
    foreach ($h['lines'] as $l) {
        $amt = max((float) $l['debit'], (float) $l['credit']);
        foreach ($needles as $n) {
            if (abs($amt - $n) < 1) {
                $interesting = true;
            }
        }
    }
    if (! $interesting) {
        continue;
    }
    echo "ref={$h['ref']} date={$h['date']} 12050 net={$h['net']}\n";
    foreach ($h['lines'] as $l) {
        echo "  {$l['gl']} {$l['name']} D={$l['debit']} C={$l['credit']} | {$l['note']}\n";
    }
    echo "\n";
}

// Also check if any entry posts to BOTH 12050 and 514
echo "=== Entries touching 12050 AND (514 or 5245 or 5239) ===\n";
foreach ($entries as $e) {
    $gls = array_column($e['lines'], 'gl_code');
    if (! in_array('12050', $gls, true)) {
        continue;
    }
    $expense = array_intersect($gls, ['514', '5245', '5239']);
    if ($expense === []) {
        continue;
    }
    echo "ref={$e['ref_no']} date={$e['operation_date']} expense=".implode(',', $expense)."\n";
    foreach ($e['lines'] as $l) {
        echo "  {$l['gl_code']} D={$l['debit']} C={$l['credit']} | {$l['note']}\n";
    }
}

// Counterparty analysis for 12050
echo "\n=== 12050 counterparties ===\n";
$cp = [];
foreach ($hits as $h) {
    foreach ($h['lines'] as $l) {
        if ($l['gl'] === '12050') {
            continue;
        }
        $key = $l['gl'];
        $cp[$key] ??= ['name' => $l['name'], 'debit' => 0.0, 'credit' => 0.0];
        $cp[$key]['debit'] += (float) $l['debit'];
        $cp[$key]['credit'] += (float) $l['credit'];
    }
}
uasort($cp, fn ($a, $b) => (abs($b['debit'] + $b['credit'])) <=> abs($a['debit'] + $a['credit']));
foreach ($cp as $gl => $x) {
    echo "$gl {$x['name']}: D=".round($x['debit'], 2).' C='.round($x['credit'], 2)."\n";
}
