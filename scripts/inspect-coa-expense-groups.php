<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = __DIR__.'/../docs/imports/american-academy-tree-of-accounts-import.xlsx';
$rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, true);

echo "Expense roots and level-2 under 5:\n";
foreach ($rows as $i => $row) {
    if ((int) $i === 1) {
        continue;
    }
    $vals = array_values($row);
    $gl = (string) ($vals[0] ?? '');
    $name = (string) ($vals[1] ?? '');
    $parent = (string) ($vals[4] ?? '');
    if ($gl === '5' || $parent === '5' || (strlen($gl) <= 3 && str_starts_with($gl, '5'))) {
        echo sprintf("%-8s parent=%-6s %s\n", $gl, $parent, $name);
    }
}

echo "\nSample under 51 / 52 / 53:\n";
foreach ($rows as $i => $row) {
    if ((int) $i === 1) {
        continue;
    }
    $vals = array_values($row);
    $gl = (string) ($vals[0] ?? '');
    $name = (string) ($vals[1] ?? '');
    $parent = (string) ($vals[4] ?? '');
    if (in_array($parent, ['51', '52', '53'], true) || in_array($gl, ['51', '52', '53', '510', '511', '520', '521'], true)) {
        echo sprintf("%-8s parent=%-6s %s\n", $gl, $parent, $name);
    }
}
