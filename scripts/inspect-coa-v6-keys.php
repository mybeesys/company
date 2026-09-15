<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$path = $argv[1] ?? 'C:/Users/ASUS/Downloads/MyBee_Master_Chart_of_Accounts_Tree_v6.xlsx';
$ss = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
$sheet = $ss->getSheetByName('دليل الحسابات COA') ?? $ss->getSheet(0);
$rows = $sheet->toArray(null, true, true, true);
array_shift($rows);

$needles = ['مردود', 'خصم', 'purchase return', 'discount', 'مكتسب', 'مشتريات', 'تكلفة'];
foreach ($rows as $r) {
    $vals = array_values($r);
    $gl = trim((string) ($vals[0] ?? ''));
    $ar = (string) ($vals[1] ?? '');
    $en = (string) ($vals[2] ?? '');
    $level = (string) ($vals[3] ?? '');
    $parent = (string) ($vals[4] ?? '');
    $hay = mb_strtolower($ar.' '.$en);
    foreach ($needles as $n) {
        if (str_contains($hay, mb_strtolower($n))) {
            echo "{$gl}\tL{$level}\tP:{$parent}\t{$ar}\t{$en}\n";
            break;
        }
    }
}

echo "\n--- levels count ---\n";
$byLevel = [];
foreach ($rows as $r) {
    $vals = array_values($r);
    $gl = trim((string) ($vals[0] ?? ''));
    if ($gl === '') continue;
    $level = (string) ($vals[3] ?? '');
    $byLevel[$level] = ($byLevel[$level] ?? 0) + 1;
}
ksort($byLevel);
print_r($byLevel);
echo 'TOTAL='.array_sum($byLevel)."\n";
