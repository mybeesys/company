<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$p = __DIR__.'/../docs/imports/american-academy-tree-of-accounts-import.xlsx';
if (! is_file($p)) {
    echo "no file\n";
    exit(1);
}

$rows = IOFactory::load($p)->getActiveSheet()->toArray(null, true, true, true);
$dep = [];
$under53 = [];

foreach ($rows as $i => $r) {
    if ((int) $i === 1) {
        continue;
    }
    $gl = trim((string) ($r['A'] ?? ''));
    $name = trim((string) ($r['B'] ?? ''));
    $parent = trim((string) ($r['C'] ?? ''));
    if ($parent === '53') {
        $under53[] = compact('gl', 'name');
    }
    if (str_contains($name, 'إهلاك') || str_contains($name, 'اهلاك') || str_contains(mb_strtolower($name), 'depreciation')) {
        $dep[] = compact('gl', 'name', 'parent');
    }
}

echo 'Direct children of 53: '.count($under53).PHP_EOL;
foreach ($under53 as $x) {
    echo "  {$x['gl']} {$x['name']}\n";
}

echo PHP_EOL.'Accounts with إهلاك in name: '.count($dep).PHP_EOL;
$byParent = [];
foreach ($dep as $d) {
    $byParent[$d['parent'] ?: '(root)'] = ($byParent[$d['parent'] ?: '(root)'] ?? 0) + 1;
}
arsort($byParent);
echo 'Depreciation leaves by parent: '.json_encode($byParent, JSON_UNESCAPED_UNICODE).PHP_EOL;
echo "Sample:\n";
foreach (array_slice($dep, 0, 10) as $d) {
    echo "  {$d['gl']} parent={$d['parent']} {$d['name']}\n";
}
