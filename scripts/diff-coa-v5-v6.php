<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Modules\Accounting\Support\MyBeeMasterCoaRules;
use PhpOffice\PhpSpreadsheet\IOFactory;

$v6 = 'C:/Users/ASUS/Downloads/MyBee_Master_Chart_of_Accounts_Tree_v6.xlsx';
$sheet = IOFactory::load($v6)->getSheet(0);

$all = [];
$l5 = [];
$skipped = [];
for ($r = 2; $r <= (int) $sheet->getHighestRow(); $r++) {
    $gl = trim((string) $sheet->getCell('A'.$r)->getFormattedValue());
    if ($gl === '') continue;
    $nameAr = MyBeeMasterCoaRules::cleanName((string) $sheet->getCell('B'.$r)->getValue());
    $nameEn = MyBeeMasterCoaRules::cleanName((string) $sheet->getCell('C'.$r)->getValue());
    $level = (int) $sheet->getCell('D'.$r)->getValue();
    $parent = trim((string) $sheet->getCell('E'.$r)->getFormattedValue());
    $all[$gl] = compact('nameAr', 'nameEn', 'level', 'parent');
    if ($level === 5) {
        $l5[] = $gl."\t".$nameAr."\t".$nameEn;
        if (MyBeeMasterCoaRules::isIllustrativePartyAccount($nameAr, $nameEn)) {
            $skipped[] = $gl."\t".$nameAr;
        }
    }
}

echo "ALL=".count($all)." L5=".count($l5)." illustrative_skipped=".count($skipped)."\n";
echo "--- L5 all ---\n".implode("\n", $l5)."\n";
echo "--- illustrative ---\n".implode("\n", $skipped)."\n";

$v5 = require __DIR__.'/../Modules/Accounting/data/mybee-master-coa-v5.php';
$v5codes = array_column($v5['accounts'], 'gl_code');
$v5types = array_column($v5['types'], 'gl_code');

$missingInV5 = [];
foreach ($all as $gl => $row) {
    if ($row['level'] <= 2) continue; // types/roots handled differently
    if (MyBeeMasterCoaRules::isIllustrativePartyAccount($row['nameAr'], $row['nameEn'])) continue;
    if (! in_array($gl, $v5codes, true)) {
        $missingInV5[] = $gl."\tL{$row['level']}\t{$row['nameAr']}";
    }
}
echo "\n--- missing in v5 accounts (non-illustrative L3+) ---\n";
echo implode("\n", $missingInV5)."\n";
echo 'count='.count($missingInV5)."\n";

foreach (['514','51401','51402','43501','42101','42201'] as $g) {
    echo "v6 has {$g}? ".(isset($all[$g]) ? 'YES '.$all[$g]['nameAr'] : 'NO')."\n";
    echo "v5 has {$g}? ".(in_array($g, $v5codes, true) || in_array($g, $v5types, true) ? 'YES' : 'NO')."\n";
}
