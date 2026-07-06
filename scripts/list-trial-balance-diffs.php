<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? '';
$wb = IOFactory::load($path);
$ws = $wb->getActiveSheet();
$maxRow = $ws->getHighestRow();

function parseNum($v): ?float
{
    if ($v === null || $v === '' || $v === '-') {
        return null;
    }
    $s = trim((string) $v);
    $s = str_replace([',', ' '], '', $s);
    if ($s === '' || $s === '-') {
        return null;
    }

    return (float) $s;
}

$diffs = [];
for ($r = 4; $r <= $maxRow; $r++) {
    $gl = trim((string) $ws->getCell("A{$r}")->getValue());
    if ($gl === '') {
        continue;
    }
    $name = (string) $ws->getCell("B{$r}")->getValue();
    $progRef = parseNum($ws->getCell("K{$r}")->getFormattedValue());
    $ledgerRef = parseNum($ws->getCell("M{$r}")->getFormattedValue());
    $diff = parseNum($ws->getCell("O{$r}")->getFormattedValue());

    if ($diff === null || abs($diff) < 0.01) {
        continue;
    }

    $diffs[] = [
        'row' => $r,
        'gl' => $gl,
        'name' => $name,
        'prog_closing' => $progRef,
        'ledger_closing' => $ledgerRef,
        'diff' => $diff,
        'debit_open' => $ws->getCell("C{$r}")->getValue(),
        'credit_open' => $ws->getCell("D{$r}")->getValue(),
        'debit_period' => $ws->getCell("E{$r}")->getValue(),
        'credit_period' => $ws->getCell("F{$r}")->getValue(),
        'debit_close' => $ws->getCell("G{$r}")->getValue(),
        'credit_close' => $ws->getCell("H{$r}")->getValue(),
    ];
}

usort($diffs, fn ($a, $b) => abs($b['diff']) <=> abs($a['diff']));

echo 'Rows with difference: '.count($diffs)."\n\n";
foreach ($diffs as $d) {
    echo sprintf(
        "GL %s | %s | diff=%.2f | prog_close=%s | ledger=%s | period D/C=%s/%s\n",
        $d['gl'],
        mb_substr($d['name'], 0, 40),
        $d['diff'],
        $d['prog_closing'] ?? 'null',
        $d['ledger_closing'] ?? 'null',
        $d['debit_period'],
        $d['credit_period']
    );
}
