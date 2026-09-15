<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$path = $argv[1] ?? 'C:/Users/ASUS/Downloads/MyBee_Master_Chart_of_Accounts_Tree_v6.xlsx';
$ss = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);

foreach ($ss->getAllSheets() as $sheet) {
    echo "=== SHEET: {$sheet->getTitle()} ===\n";
    $rows = $sheet->toArray(null, true, true, true);
    $i = 0;
    foreach ($rows as $r) {
        if ($i < 8) {
            echo json_encode(array_values($r), JSON_UNESCAPED_UNICODE)."\n";
        }
        $i++;
    }
    echo "TOTAL=".$i."\n\n";
}
