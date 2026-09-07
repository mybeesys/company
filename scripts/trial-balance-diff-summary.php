<?php

require __DIR__.'/../vendor/autoload.php';

use Modules\Accounting\Services\JournalEntry\JournalTransactionsExcelParser;
use PhpOffice\PhpSpreadsheet\IOFactory;

$tbPath = $argv[1] ?? 'C:/Users/ASUS/Downloads/trial-balance-20260907-073957.xlsx';
$journalPath = $argv[2] ?? 'C:/Users/ASUS/Downloads/Journal Transactions (1) (1).xls';

$sheet = IOFactory::load($tbPath)->getActiveSheet();
$highestRow = (int) $sheet->getHighestRow();

function num($sheet, string $coord): float
{
    $v = $sheet->getCell($coord)->getCalculatedValue();

    return ($v === null || $v === '') ? 0.0 : round((float) str_replace(',', '', (string) $v), 2);
}

echo "META:\n";
echo trim((string) $sheet->getCell('A1')->getValue())."\n";
echo trim((string) $sheet->getCell('A2')->getValue())."\n\n";

echo "HEADERS:\n";
foreach (range('A', 'O') as $col) {
    $h = trim((string) $sheet->getCell($col.'3')->getValue());
    if ($h !== '') {
        echo "  $col: $h\n";
    }
}
echo "\n";

// New layout: A gl, B name, C open D, D open C, E period D, F period C, G period net,
// H close D, I close C, then external likely L/M/N/O
$diffs = [];
$zero = 0;
$sumO = 0.0;
$sumBeePeriodD = 0.0;
$sumBeePeriodC = 0.0;

for ($r = 4; $r <= $highestRow; $r++) {
    $gl = trim((string) $sheet->getCell('A'.$r)->getValue());
    $name = trim((string) $sheet->getCell('B'.$r)->getValue());
    if ($gl === '' || str_contains($name, 'المجموع')) {
        continue;
    }

    $bee = [
        'open_d' => num($sheet, 'C'.$r),
        'open_c' => num($sheet, 'D'.$r),
        'period_d' => num($sheet, 'E'.$r),
        'period_c' => num($sheet, 'F'.$r),
        'period_net' => num($sheet, 'G'.$r),
        'close_d' => num($sheet, 'H'.$r),
        'close_c' => num($sheet, 'I'.$r),
    ];
    $bee['close_net'] = round($bee['close_d'] - $bee['close_c'], 2);

    $sumBeePeriodD += $bee['period_d'];
    $sumBeePeriodC += $bee['period_c'];

    // Detect external columns: look for values in J..O
    $ext = [];
    foreach (range('J', 'O') as $col) {
        $raw = $sheet->getCell($col.$r)->getCalculatedValue();
        if ($raw === null || $raw === '') {
            continue;
        }
        if (! is_numeric($raw) && ! is_numeric(str_replace([',', ' '], '', (string) $raw))) {
            $ext['name'] = trim((string) $raw);
        } else {
            $ext[$col] = round((float) str_replace(',', '', (string) $raw), 2);
        }
    }

    $o = $ext['O'] ?? null;
    if ($o === null) {
        // try compute from closing if L/M look like close
        if (isset($ext['L'], $ext['M'])) {
            $extCloseNet = round($ext['L'] - $ext['M'], 2);
            $o = round($bee['close_net'] - $extCloseNet, 2);
        } elseif (isset($ext['K'])) {
            // formula cell K may be bee close net
            $o = null;
        }
    }

    if ($o === null || abs($o) < 0.005) {
        $zero++;
        continue;
    }

    $sumO += $o;
    $diffs[] = [
        'row' => $r,
        'gl' => $gl,
        'name' => $name,
        'bee' => $bee,
        'ext' => $ext,
        'diff_O' => $o,
    ];
}

usort($diffs, fn ($a, $b) => abs($b['diff_O']) <=> abs($a['diff_O']));

echo "Bee period totals: D=".number_format(round($sumBeePeriodD, 2), 2).' C='.number_format(round($sumBeePeriodC, 2), 2)."\n";
echo "Matching (O~0): $zero | Diff rows: ".count($diffs).' | Sum O='.round($sumO, 2)."\n\n";

echo "=== TOP DIFFS ===\n";
foreach (array_slice($diffs, 0, 25) as $d) {
    echo sprintf(
        "GL %s | Diff=%s | Bee close_net=%s period_d=%s period_c=%s | Ext=%s\n",
        $d['gl'],
        number_format($d['diff_O'], 2),
        number_format($d['bee']['close_net'], 2),
        number_format($d['bee']['period_d'], 2),
        number_format($d['bee']['period_c'], 2),
        json_encode($d['ext'], JSON_UNESCAPED_UNICODE)
    );
}

// Journal parse
$parser = new JournalTransactionsExcelParser();
$parsed = $parser->parse($journalPath);
$entries = $parsed['entries'];
$dates = array_column($entries, 'operation_date');
sort($dates);

$targetGls = array_unique(array_merge(array_column($diffs, 'gl'), ['514', '5245', '5239', '12050', '32', '36', '223', '222']));
$glTotals = [];
foreach ($entries as $e) {
    foreach ($e['lines'] as $line) {
        $gl = $line['gl_code'];
        if (! in_array($gl, $targetGls, true)) {
            continue;
        }
        $glTotals[$gl] ??= ['debit' => 0.0, 'credit' => 0.0, 'refs' => []];
        $glTotals[$gl]['debit'] += (float) $line['debit'];
        $glTotals[$gl]['credit'] += (float) $line['credit'];
        $glTotals[$gl]['refs'][$e['ref_no']] = true;
    }
}

echo "\n=== JOURNAL FILE ===\n";
echo 'Entries: '.count($entries).' | Errors: '.count($parsed['errors'])."\n";
echo 'Date range: '.($dates[0] ?? '?').' -> '.($dates[count($dates) - 1] ?? '?')."\n";
echo 'Ref range: '.min(array_map('intval', array_column($entries, 'ref_no'))).' - '.max(array_map('intval', array_column($entries, 'ref_no')))."\n";
$jd = 0.0;
$jc = 0.0;
foreach ($entries as $e) {
    foreach ($e['lines'] as $l) {
        $jd += (float) $l['debit'];
        $jc += (float) $l['credit'];
    }
}
echo 'Journal totals: D='.number_format(round($jd, 2), 2).' C='.number_format(round($jc, 2), 2)."\n";
echo 'Journal == Bee TB period: '.(abs(round($jd, 2) - round($sumBeePeriodD, 2)) < 0.02 ? 'YES' : 'NO')."\n";

echo "\n=== KEY GL: FILE vs BEE TB ===\n";
foreach ($diffs as $d) {
    $gl = $d['gl'];
    $file = $glTotals[$gl] ?? ['debit' => 0, 'credit' => 0, 'refs' => []];
    $fd = round($file['debit'] ?? 0, 2);
    $fc = round($file['credit'] ?? 0, 2);
    echo sprintf(
        "GL %s: file D/C=%s/%s | bee period D/C=%s/%s | file-bee D=%s | refs=%d | DiffO=%s\n",
        $gl,
        number_format($fd, 2),
        number_format($fc, 2),
        number_format($d['bee']['period_d'], 2),
        number_format($d['bee']['period_c'], 2),
        number_format($fd - $d['bee']['period_d'], 2),
        count($file['refs'] ?? []),
        number_format($d['diff_O'], 2)
    );
}

// Offset pairs
echo "\n=== POSSIBLE OFFSET PAIRS (diff nearly cancel) ===\n";
for ($i = 0; $i < count($diffs); $i++) {
    for ($j = $i + 1; $j < count($diffs); $j++) {
        $sum = round($diffs[$i]['diff_O'] + $diffs[$j]['diff_O'], 2);
        if (abs($sum) <= 1.0 && abs($diffs[$i]['diff_O']) > 100) {
            echo sprintf(
                "GL %s (%s) + GL %s (%s) = %s\n",
                $diffs[$i]['gl'],
                number_format($diffs[$i]['diff_O'], 2),
                $diffs[$j]['gl'],
                number_format($diffs[$j]['diff_O'], 2),
                number_format($sum, 2)
            );
        }
    }
}
