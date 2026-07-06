<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1];
$wb = IOFactory::load($path);
$ws = $wb->getActiveSheet();
$maxRow = $ws->getHighestRow();

$meta = [
    'ref' => (string) $ws->getCell('A1')->getValue(),
    'date' => (string) $ws->getCell('B1')->getValue(),
    'note' => (string) $ws->getCell('C1')->getValue(),
];
echo "Meta: ".json_encode($meta, JSON_UNESCAPED_UNICODE)."\n\n";

function extractGl(string $cell): string
{
    $s = trim($cell);
    if (preg_match('/^([\d]+)\s*[-–]/u', $s, $m)) {
        return $m[1];
    }
    if (preg_match('/^(\d+)$/', $s)) {
        return $s;
    }

    return '';
}

$lines = [];
$debit = 0.0;
$credit = 0.0;

for ($r = 3; $r <= $maxRow; $r++) {
    $accountCell = trim((string) $ws->getCell("A{$r}")->getValue());
    if ($accountCell === '') {
        continue;
    }
    if (preg_match('/مجموع|total|إجمالي/i', $accountCell)) {
        continue;
    }

    $gl = extractGl($accountCell);
    $d = (float) str_replace(',', '', (string) $ws->getCell("C{$r}")->getValue());
    $c = (float) str_replace(',', '', (string) $ws->getCell("D{$r}")->getValue());

    if ($d == 0 && $c == 0) {
        continue;
    }
    if ($d > 0 && $c > 0) {
        echo "WARN R{$r} both sides: {$accountCell} D={$d} C={$c}\n";
    }

    $debit += $d;
    $credit += $c;
    $lines[] = compact('r', 'gl', 'accountCell', 'd', 'c');
}

echo 'Lines: '.count($lines)."\n";
echo "Debit: {$debit}\n";
echo "Credit: {$credit}\n";
echo 'Diff: '.abs($debit - $credit)."\n\n";

foreach (['31', '221', '222'] as $want) {
    echo "=== GL {$want} ===\n";
    $found = false;
    foreach ($lines as $l) {
        if ($l['gl'] === $want) {
            $found = true;
            echo "R{$l['r']}: {$l['accountCell']} | D={$l['d']} C={$l['c']}\n";
        }
    }
    if (! $found) {
        echo "NOT FOUND\n";
    }
}

// liability/equity credits summary
echo "\n=== Key liability/equity lines (gl starting 2 or 3, credit > 0) ===\n";
foreach ($lines as $l) {
    if ($l['c'] > 0 && preg_match('/^(2|3)\d*$/', $l['gl'])) {
        echo "GL {$l['gl']}: C={$l['c']} | ".mb_substr($l['accountCell'], 0, 55)."\n";
    }
}

// tail rows
echo "\n=== Last 20 rows raw ===\n";
for ($r = max(3, $maxRow - 19); $r <= $maxRow; $r++) {
    echo "R{$r}: A=".trim((string) $ws->getCell("A{$r}")->getValue())
        .' | D='.$ws->getCell("C{$r}")->getValue()
        .' | C='.$ws->getCell("D{$r}")->getValue()."\n";
}

$counts = [];
foreach ($lines as $l) {
    if ($l['gl'] !== '') {
        $counts[$l['gl']] = ($counts[$l['gl']] ?? 0) + 1;
    }
}
$dups = array_filter($counts, fn ($n) => $n > 1);
if ($dups) {
    echo "\nDuplicate GL in file:\n";
    foreach ($dups as $gl => $n) {
        echo "  {$gl}: {$n}x\n";
    }
}
