<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$path = $argv[1] ?? 'C:/Users/ASUS/Downloads/trial-balance-20260831-121529.xlsx';

$spreadsheet = IOFactory::load($path);

foreach ($spreadsheet->getAllSheets() as $sheetIndex => $sheet) {
    echo "=== Sheet {$sheetIndex}: {$sheet->getTitle()} ===\n";
    $highestRow = (int) $sheet->getHighestRow();
    echo "Rows: {$highestRow}, Cols: {$sheet->getHighestColumn()}\n";

    // Print first 5 rows
    for ($r = 1; $r <= min(5, $highestRow); $r++) {
        $cells = [];
        foreach (range('A', min('L', $sheet->getHighestColumn())) as $col) {
            $cells[] = $col.':'.trim((string) $sheet->getCell($col.$r)->getValue());
        }
        echo "R{$r}: ".implode(' | ', $cells)."\n";
    }

    $colorCounts = [];
    $coloredRows = [];

    for ($r = 1; $r <= $highestRow; $r++) {
        $rowColors = [];
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $fill = $sheet->getStyle($col.$r)->getFill();
            $type = $fill->getFillType();
            if ($type === Fill::FILL_NONE) {
                continue;
            }
            $rgb = strtoupper((string) $fill->getStartColor()->getRGB());
            if ($rgb === '' || $rgb === '000000' || $rgb === 'FFFFFF' || $rgb === 'FFFFFFFF') {
                continue;
            }
            $colorCounts[$rgb] = ($colorCounts[$rgb] ?? 0) + 1;
            $rowColors[$col] = $rgb;
        }
        if ($rowColors !== []) {
            $gl = trim((string) $sheet->getCell('A'.$r)->getValue());
            $name = trim((string) $sheet->getCell('B'.$r)->getValue());
            $coloredRows[] = ['row' => $r, 'gl' => $gl, 'name' => $name, 'colors' => $rowColors];
        }
    }

    echo 'Fill color counts: '.json_encode($colorCounts, JSON_UNESCAPED_UNICODE)."\n";
    echo "Colored rows sample (first 30):\n";
    foreach (array_slice($coloredRows, 0, 30) as $cr) {
        echo json_encode($cr, JSON_UNESCAPED_UNICODE)."\n";
    }
    echo "\n";
}
