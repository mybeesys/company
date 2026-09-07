<?php

require __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$journal = 'C:/Users/ASUS/Downloads/Journal Transactions (1).xls';
$sheet = IOFactory::load($journal)->getSheetByName('Journal Transactions (2)') ?? IOFactory::load($journal)->getActiveSheet();
$rows = $sheet->toArray(null, true, true, true);

$needles = ['راتب 4 ايام', 'Mamdouh Awad', 'Abubakar Sadiq', 'Haval Abdulaziz', 'Corey John', 'يونيو 2026'];
foreach ($rows as $rowNum => $row) {
    $vals = array_values($row);
    $text = json_encode($vals, JSON_UNESCAPED_UNICODE);
    foreach ($needles as $n) {
        if (str_contains($text, $n)) {
            echo "R$rowNum: ".json_encode(array_slice($vals, 0, 8), JSON_UNESCAPED_UNICODE)."\n";
        }
    }
}

// Find small payroll entries (single 514 line + bank credit) near July
$refs = [];
$current = null;
foreach ($rows as $row) {
    $vals = array_values($row);
    $a = trim((string) ($vals[0] ?? ''));
    $b = trim((string) ($vals[1] ?? ''));
    if ($a !== '' && preg_match('/^\d+$/', $a) && $b === '') {
        $current = $a;
    }
    if ($b !== '' && str_contains((string) ($vals[2] ?? ''), '514') && (float) str_replace(',', '', (string) ($vals[6] ?? 0)) < 30000) {
        // capture small payroll pattern entries in July
        if (str_contains((string) ($vals[0] ?? ''), '7/') || str_contains((string) ($vals[0] ?? ''), '07/')) {
            $refs[$b ?: $current][] = array_slice($vals, 0, 8);
        }
    }
}
echo "\nJuly small 514 entries sample:\n";
$i = 0;
foreach ($refs as $ref => $lines) {
    if (count($lines) <= 4) {
        echo "REF $ref:\n";
        foreach ($lines as $l) {
            echo '  '.json_encode($l, JSON_UNESCAPED_UNICODE)."\n";
        }
        if (++$i >= 5) {
            break;
        }
    }
}
