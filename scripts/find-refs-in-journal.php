<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = 'C:/Users/ASUS/Downloads/Journal Transactions.xls';
$targets = ['9128', '9129', '9131'];

if (! is_file($path)) {
    echo "File missing: $path\n";
    // try variants
    foreach (glob('C:/Users/ASUS/Downloads/Journal*.xls*') ?: [] as $f) {
        echo "Found: $f size=".filesize($f)."\n";
    }
    exit(1);
}

echo "Loading $path (".filesize($path)." bytes)\n";
$ss = IOFactory::load($path);
foreach ($ss->getAllSheets() as $sheet) {
    echo "Sheet: ".$sheet->getTitle()." rows=".$sheet->getHighestRow()."\n";
}

$sheet = $ss->getSheetByName('Journal Transactions (2)') ?? $ss->getActiveSheet();
$rows = $sheet->toArray(null, true, true, true);

$capture = null;
$blocks = [];
foreach ($rows as $rowNum => $row) {
    $vals = array_values($row);
    $a = trim((string) ($vals[0] ?? ''));
    $b = trim((string) ($vals[1] ?? ''));
    $aNorm = ltrim($a, '0') ?: '0';
    $bNorm = ltrim($b, '0') ?: '0';

    if ($a !== '' && preg_match('/^\d+$/', $a) && $b === '') {
        if ($capture !== null && in_array($capture, $targets, true)) {
            // already stored
        }
        $capture = $aNorm;
        if (in_array($capture, $targets, true)) {
            $blocks[$capture] = [];
        }
        continue;
    }

    if ($capture !== null && in_array($capture, $targets, true)) {
        if ($a !== '' && preg_match('/^\d+$/', $a) && $b === '' && $aNorm !== $capture) {
            $capture = $aNorm;
            continue;
        }
        $blocks[$capture][] = ['row' => $rowNum, 'vals' => array_slice($vals, 0, 8)];
    } elseif (in_array($bNorm, $targets, true)) {
        $blocks[$bNorm][] = ['row' => $rowNum, 'vals' => array_slice($vals, 0, 8)];
    }
}

foreach ($targets as $t) {
    echo "\n===== REF $t =====\n";
    if (! isset($blocks[$t]) || $blocks[$t] === []) {
        echo "NOT FOUND\n";
        continue;
    }
    foreach ($blocks[$t] as $line) {
        echo 'R'.$line['row'].': '.json_encode($line['vals'], JSON_UNESCAPED_UNICODE)."\n";
    }
}

// Also compute max ref and date of highest refs
$maxRef = 0;
$maxDate = null;
foreach ($rows as $row) {
    $vals = array_values($row);
    $b = ltrim(trim((string) ($vals[1] ?? '')), '0');
    if ($b !== '' && ctype_digit($b) && (int) $b > $maxRef) {
        $maxRef = (int) $b;
        $maxDate = $vals[0] ?? null;
    }
}
echo "\nMax ref seen in col B: $maxRef date_raw=".json_encode($maxDate)."\n";
