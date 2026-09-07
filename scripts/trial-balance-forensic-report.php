<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? 'C:/Users/ASUS/Downloads/trial-balance-20260831-121529.xlsx';
$sheet = IOFactory::load($path)->getActiveSheet();
$highestRow = (int) $sheet->getHighestRow();

function num($sheet, string $coord): float
{
    $v = $sheet->getCell($coord)->getCalculatedValue();

    return ($v === null || $v === '') ? 0.0 : round((float) str_replace(',', '', (string) $v), 2);
}

$rows = [];
for ($r = 4; $r <= $highestRow; $r++) {
    $gl = trim((string) $sheet->getCell('A'.$r)->getValue());
    $name = trim((string) $sheet->getCell('B'.$r)->getValue());
    if ($gl === '' || str_contains($name, 'المجموع')) {
        continue;
    }
    $o = num($sheet, 'O'.$r);
    if (abs($o) < 0.01) {
        continue;
    }

    $beeOpenNet = round(num($sheet, 'C'.$r) - num($sheet, 'D'.$r), 2);
    $beePeriodNet = round(num($sheet, 'E'.$r) - num($sheet, 'F'.$r), 2);
    $beeCloseNet = round(num($sheet, 'G'.$r) - num($sheet, 'H'.$r), 2);
    $extCloseNet = round(num($sheet, 'K'.$r) - num($sheet, 'L'.$r), 2);
    $extPeriodNet = num($sheet, 'M'.$r);

    $rows[] = compact('r', 'gl', 'name', 'o', 'beeOpenNet', 'beePeriodNet', 'beeCloseNet', 'extCloseNet', 'extPeriodNet');
}

// Categories
$cats = [
    'retained_earnings_32_36' => [],
    'vat_cluster' => [],
    'bank_accounts' => [],
    'expense_period_missing' => [],
    'related_party' => [],
    'rounding_only' => [],
    'other' => [],
];

foreach ($rows as $row) {
    $gl = $row['gl'];
    $absO = abs($row['o']);

    if (in_array($gl, ['32', '36'], true)) {
        $cats['retained_earnings_32_36'][] = $row;
    } elseif (in_array($gl, ['215001', '215002', '2214', '221'], true) || str_starts_with($gl, '215')) {
        $cats['vat_cluster'][] = $row;
    } elseif (str_starts_with($gl, '1202') || str_starts_with($gl, '1204')) {
        $cats['bank_accounts'][] = $row;
    } elseif (str_starts_with($gl, '5') && $row['beeOpenNet'] == 0 && abs($row['beePeriodNet'] - $row['extPeriodNet']) >= 0.01) {
        $cats['expense_period_missing'][] = $row;
    } elseif (str_starts_with($gl, '1207') || str_starts_with($gl, '222')) {
        $cats['related_party'][] = $row;
    } elseif ($absO <= 0.02) {
        $cats['rounding_only'][] = $row;
    } else {
        $cats['other'][] = $row;
    }
}

$sumCat = fn (array $list) => round(array_sum(array_column($list, 'o')), 2);

echo "=== TRIAL BALANCE VARIANCE FORENSIC REPORT ===\n\n";
echo "Period: 2026-01-01 to 2026-07-31\n";
echo 'Accounts with variance: '.count($rows)."\n";
echo 'Total net variance (sum O): '.round(array_sum(array_column($rows, 'o')), 2)." SAR\n\n";

echo "--- BY ROOT CAUSE ---\n\n";

echo "1) RETAINED EARNINGS SPLIT (32 vs 36) — Opening balance mapping\n";
echo '   Sum variance: '.$sumCat($cats['retained_earnings_32_36'])." SAR (nearly offsetting)\n";
foreach ($cats['retained_earnings_32_36'] as $x) {
    echo "   GL {$x['gl']}: Bee close=".number_format($x['beeCloseNet'], 2).' | Ext close='.number_format($x['extCloseNet'], 2).' | Diff='.number_format($x['o'], 2)."\n";
}
echo "   → Bee shows 12.07M in acct 32 vs 5.93M in external; acct 36 inverted.\n";
echo "   → NOT a journal import error — opening/P&L closing entry classification differs.\n\n";

echo "2) VAT CLUSTER (215001, 215002, 2214)\n";
echo '   Sum variance: '.$sumCat($cats['vat_cluster'])." SAR\n";
foreach ($cats['vat_cluster'] as $x) {
    echo "   GL {$x['gl']} ({$x['name']}): Diff=".number_format($x['o'], 2).' | Bee period net='.number_format($x['beePeriodNet'], 2).' | Ext='.number_format($x['extPeriodNet'], 2)."\n";
}
echo "   → VAT collected/paid posted to different clearing accounts vs external.\n\n";

echo "3) BANK / CASH ACCOUNTS\n";
echo '   Sum variance: '.$sumCat($cats['bank_accounts'])." SAR\n";
foreach ($cats['bank_accounts'] as $x) {
    echo "   GL {$x['gl']}: Diff=".number_format($x['o'], 2).' | Bee period='.number_format($x['beePeriodNet'], 2).' | Ext period='.number_format($x['extPeriodNet'], 2)."\n";
}
echo "\n";

echo "4) EXPENSE ACCOUNTS — Period movement gaps (likely missing/wrong journals)\n";
echo '   Count: '.count($cats['expense_period_missing']).' | Sum variance: '.$sumCat($cats['expense_period_missing'])." SAR\n";
usort($cats['expense_period_missing'], fn ($a, $b) => abs($b['o']) <=> abs($a['o']));
foreach (array_slice($cats['expense_period_missing'], 0, 20) as $x) {
    $periodGap = round($x['beePeriodNet'] - $x['extPeriodNet'], 2);
    echo "   GL {$x['gl']} {$x['name']}: Diff=".number_format($x['o'], 2).' | Period gap Bee-Ext='.number_format($periodGap, 2)."\n";
}
echo "\n";

echo "5) RELATED PARTY (222x, 1207x)\n";
echo '   Sum variance: '.$sumCat($cats['related_party'])." SAR\n";
foreach ($cats['related_party'] as $x) {
    echo "   GL {$x['gl']}: Diff=".number_format($x['o'], 2)."\n";
}
echo "\n";

echo "6) ROUNDING ONLY (<= 0.02)\n";
echo '   Count: '.count($cats['rounding_only']).' | Sum: '.$sumCat($cats['rounding_only'])."\n\n";

echo "7) OTHER\n";
echo '   Sum variance: '.$sumCat($cats['other'])." SAR\n";
foreach ($cats['other'] as $x) {
    echo "   GL {$x['gl']} {$x['name']}: Diff=".number_format($x['o'], 2)."\n";
}

// Expense round-number pattern
$roundDiffs = array_filter($cats['expense_period_missing'], fn ($x) => fmod(abs($x['o']), 100) == 0 || fmod(abs($x['o']), 50) == 0);
echo "\n--- EXPENSE ROUND-AMOUNT PATTERN (suggests specific missing vouchers) ---\n";
echo 'Count with round diffs (÷50): '.count($roundDiffs)."\n";
$roundSum = round(array_sum(array_map(fn ($x) => $x['o'], $roundDiffs)), 2);
echo "Sum of round diffs: {$roundSum}\n";

// Check if My Bee TB is internally balanced
$totalBeeDebitPeriod = 0;
$totalBeeCreditPeriod = 0;
for ($r = 4; $r <= $highestRow; $r++) {
    $gl = trim((string) $sheet->getCell('A'.$r)->getValue());
    if ($gl === '') {
        continue;
    }
    $totalBeeDebitPeriod += num($sheet, 'E'.$r);
    $totalBeeCreditPeriod += num($sheet, 'F'.$r);
}
echo "\n--- MY BEE INTERNAL CONSISTENCY ---\n";
echo 'Total period debit: '.number_format($totalBeeDebitPeriod, 2)."\n";
echo 'Total period credit: '.number_format($totalBeeCreditPeriod, 2)."\n";
echo 'Period difference: '.number_format($totalBeeDebitPeriod - $totalBeeCreditPeriod, 2)." (should be 0)\n";
