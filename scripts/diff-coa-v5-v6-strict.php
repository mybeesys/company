<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use Modules\Accounting\Support\MyBeeMasterCoaRules;
use PhpOffice\PhpSpreadsheet\IOFactory;

$v6 = 'C:/Users/ASUS/Downloads/MyBee_Master_Chart_of_Accounts_Tree_v6.xlsx';
$sheet = IOFactory::load($v6)->getSheet(0);
$v5 = require __DIR__.'/../Modules/Accounting/data/mybee-master-coa-v5.php';
$v5codes = array_map('strval', array_column($v5['accounts'], 'gl_code'));
$v5types = array_map('strval', array_column($v5['types'], 'gl_code'));

$missing = [];
$extra = [];
$v6AccountCodes = [];

for ($r = 2; $r <= (int) $sheet->getHighestRow(); $r++) {
    $gl = trim((string) $sheet->getCell('A'.$r)->getFormattedValue());
    if ($gl === '') continue;
    $nameAr = MyBeeMasterCoaRules::cleanName((string) $sheet->getCell('B'.$r)->getValue());
    $nameEn = MyBeeMasterCoaRules::cleanName((string) $sheet->getCell('C'.$r)->getValue());
    $level = (int) $sheet->getCell('D'.$r)->getValue();
    if ($level <= 2) continue;
    if (MyBeeMasterCoaRules::isIllustrativePartyAccount($nameAr, $nameEn)) continue;
    $v6AccountCodes[] = $gl;
    if (! in_array($gl, $v5codes, true)) {
        $missing[] = "{$gl}\tL{$level}\t{$nameAr}\t{$nameEn}";
    }
}

foreach ($v5codes as $gl) {
    if (! in_array($gl, $v6AccountCodes, true)) {
        $extra[] = $gl;
    }
}

echo "v5 accounts=".count($v5codes)." v6 non-illustrative L3+=".count($v6AccountCodes)."\n";
echo "missing_in_v5=".count($missing)." extra_in_v5_not_v6=".count($extra)."\n";
echo "--- MISSING ---\n".implode("\n", $missing)."\n";
if ($extra) echo "--- EXTRA ---\n".implode("\n", $extra)."\n";
