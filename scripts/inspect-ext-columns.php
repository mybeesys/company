<?php
require __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$path = 'C:/Users/ASUS/Downloads/trial-balance-20260907-073957.xlsx';
$sheet = IOFactory::load($path)->getActiveSheet();

function n($s, $c) {
    $v = $s->getCell($c)->getCalculatedValue();
    return ($v === null || $v === '') ? 0 : round((float)$v, 2);
}

foreach ([4, 66, 168, 211, 212, 218, 204, 80] as $r) {
    $gl = $sheet->getCell("A$r")->getValue();
    echo "Row $r GL=$gl\n";
    foreach (range('A', 'O') as $col) {
        $v = $sheet->getCell($col.$r)->getCalculatedValue();
        if ($v !== null && $v !== '') {
            echo "  $col=".json_encode($v, JSON_UNESCAPED_UNICODE)."\n";
        }
    }
    echo "\n";
}

// Find 514 and 12050 rows
$highest = (int)$sheet->getHighestRow();
for ($r = 4; $r <= $highest; $r++) {
    $gl = trim((string)$sheet->getCell("A$r")->getValue());
    if (in_array($gl, ['514','5245','5239','12050','32','36','223','222','215001','2214'], true)) {
        echo "FOUND $gl row $r: ";
        echo "C=".n($sheet,"C$r")." D=".n($sheet,"D$r")." E=".n($sheet,"E$r")." F=".n($sheet,"F$r")." G=".n($sheet,"G$r")." H=".n($sheet,"H$r")." I=".n($sheet,"I$r");
        echo " J=".json_encode($sheet->getCell("J$r")->getValue(), JSON_UNESCAPED_UNICODE);
        echo " K=".n($sheet,"K$r")." L=".n($sheet,"L$r")." M=".n($sheet,"M$r")." N=".n($sheet,"N$r")." O=".n($sheet,"O$r")."\n";
    }
}
