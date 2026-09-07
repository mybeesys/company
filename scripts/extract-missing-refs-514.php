<?php

require __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$path = 'C:/Users/ASUS/Downloads/6a95817714f25-حساب الأستاذ.xls';
$sheet = IOFactory::load($path)->getActiveSheet();
$rows = $sheet->toArray(null, true, true, true);

$targets = ['9014', '9034', '9035', '9038'];
$found = [];

foreach ($rows as $rowNum => $row) {
    $vals = array_values($row);
    $ref = ltrim(trim((string) ($vals[1] ?? '')), '0') ?: '0';
    if (! in_array($ref, $targets, true)) {
        continue;
    }
    $found[] = [
        'row' => $rowNum,
        'ref' => $ref,
        'transfer_id' => trim((string) ($vals[2] ?? '')),
        'date' => trim((string) ($vals[3] ?? '')),
        'employee' => trim((string) ($vals[4] ?? '')),
        'description' => trim((string) ($vals[5] ?? '')),
        'debit' => trim((string) ($vals[6] ?? '')),
        'credit' => trim((string) ($vals[7] ?? '')),
    ];
}

echo json_encode($found, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;

// Also check max ref in ledger
$maxRef = 0;
foreach ($rows as $row) {
    $vals = array_values($row);
    $ref = (int) ltrim(trim((string) ($vals[1] ?? '')), '0');
    if ($ref > $maxRef) {
        $maxRef = $ref;
    }
}
echo "Max ref in 514 ledger: $maxRef\n";
