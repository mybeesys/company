<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? '';
if (! is_file($path)) {
    fwrite(STDERR, "File not found: {$path}\n");
    exit(1);
}

$wb = IOFactory::load($path);
$ws = $wb->getActiveSheet();
$maxRow = $ws->getHighestRow();
$maxCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($ws->getHighestColumn());

echo "Rows: {$maxRow}, Cols: {$maxCol}\n\n";

for ($r = 1; $r <= min(15, $maxRow); $r++) {
    $cells = [];
    for ($c = 1; $c <= $maxCol; $c++) {
        $cells[] = $ws->getCellByColumnAndRow($c, $r)->getFormattedValue();
    }
    echo "R{$r}: ".json_encode($cells, JSON_UNESCAPED_UNICODE)."\n";
}

$debitTotal = 0.0;
$creditTotal = 0.0;
$lines = [];
$headerRow = null;

for ($r = 1; $r <= $maxRow; $r++) {
    $row = [];
    for ($c = 1; $c <= $maxCol; $c++) {
        $row[] = trim((string) $ws->getCellByColumnAndRow($c, $r)->getValue());
    }
    $joined = mb_strtolower(implode('|', $row));

    if ($headerRow === null && (str_contains($joined, 'gl') || str_contains($joined, 'مدين') || str_contains($joined, 'debit'))) {
        $headerRow = $r;
        echo "\nHeader at R{$r}: ".json_encode($row, JSON_UNESCAPED_UNICODE)."\n";
        continue;
    }

    if ($headerRow === null) {
        continue;
    }

    // skip empty
    if (implode('', $row) === '') {
        continue;
    }
    if (preg_match('/مجموع|total|المجموع/i', $row[0] ?? '')) {
        continue;
    }

    $gl = '';
    $name = '';
    $debit = 0.0;
    $credit = 0.0;

    // Try common column layouts
    foreach ($row as $i => $val) {
        if ($val === '') {
            continue;
        }
        if (preg_match('/^\d+(\.\d+)?$/', str_replace(',', '', $val)) && $i >= 2) {
            $num = (float) str_replace(',', '', $val);
            if ($debit == 0 && ($row[$i + 1] ?? '') === '' || isset($row[$i + 1])) {
                // heuristic: find debit/credit columns from header
            }
        }
    }
}

// Use OpeningBalanceExcelParser if available
$parser = new \Modules\Accounting\Services\JournalEntry\OpeningBalanceExcelParser();
$parsed = $parser->parse($path);

echo "\n=== OpeningBalanceExcelParser result ===\n";
echo 'Parse errors: '.count($parsed['errors'])."\n";
if ($parsed['errors'] !== []) {
    foreach (array_slice($parsed['errors'], 0, 20) as $e) {
        echo "  Row {$e['row']}: {$e['message']}\n";
    }
}
echo 'Lines: '.count($parsed['lines'])."\n";
echo "Debit total: {$parsed['debit_total']}\n";
echo "Credit total: {$parsed['credit_total']}\n";
echo 'Balanced: '.(abs((float)$parsed['debit_total'] - (float)$parsed['credit_total']) < 0.02 ? 'YES' : 'NO')."\n";

$targetGls = ['31', '221', '222'];
echo "\n=== Target accounts (31, 221, 222) ===\n";
foreach ($parsed['lines'] as $line) {
    if (in_array($line['gl_code'], $targetGls, true)) {
        echo "GL {$line['gl_code']} | {$line['account_name']} | D={$line['debit']} C={$line['credit']}\n";
    }
}

$missing = array_filter($targetGls, function ($gl) use ($parsed) {
    foreach ($parsed['lines'] as $line) {
        if ($line['gl_code'] === $gl) {
            return false;
        }
    }
    return true;
});
if ($missing !== []) {
    echo 'MISSING from file: '.implode(', ', $missing)."\n";
}

// Duplicate GL codes
$glCounts = [];
foreach ($parsed['lines'] as $line) {
    $glCounts[$line['gl_code']] = ($glCounts[$line['gl_code']] ?? 0) + 1;
}
$dups = array_filter($glCounts, fn ($c) => $c > 1);
if ($dups !== []) {
    echo "\nDuplicate GL codes:\n";
    foreach ($dups as $gl => $c) {
        echo "  {$gl}: {$c} times\n";
    }
}

// Both debit and credit on same line
echo "\n=== Lines with both debit and credit ===\n";
foreach ($parsed['lines'] as $line) {
    if ((float) $line['debit'] > 0 && (float) $line['credit'] > 0) {
        echo "GL {$line['gl_code']} D={$line['debit']} C={$line['credit']}\n";
    }
}

// Zero lines
$zeros = array_filter($parsed['lines'], fn ($l) => (float)$l['debit'] == 0 && (float)$l['credit'] == 0);
echo 'Zero amount lines: '.count($zeros)."\n";

// Sum check raw
$rawDebit = 0;
$rawCredit = 0;
foreach ($parsed['lines'] as $line) {
    $rawDebit += (float) $line['debit'];
    $rawCredit += (float) $line['credit'];
}
echo "Raw sum debit: {$rawDebit}, credit: {$rawCredit}, diff: ".abs($rawDebit - $rawCredit)."\n";
