<?php

require __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$path = 'C:/Users/ASUS/Downloads/Journal Transactions (1).xls';
$sheet = IOFactory::load($path)->getSheetByName('Journal Transactions (2)') ?? IOFactory::load($path)->getActiveSheet();
$rows = $sheet->toArray(null, true, true, true);

function dumpRef($rows, $target)
{
    $capture = false;
    foreach ($rows as $rowNum => $row) {
        $vals = array_values($row);
        $a = trim((string) ($vals[0] ?? ''));
        if ($a === (string) $target && trim((string) ($vals[1] ?? '')) === '') {
            $capture = true;
            echo "--- ref $target ---\n";

            continue;
        }
        if ($capture) {
            if ($a !== '' && preg_match('/^\d+$/', $a) && trim((string) ($vals[1] ?? '')) === '' && $a !== (string) $target) {
                break;
            }
            echo json_encode(array_slice($vals, 0, 8), JSON_UNESCAPED_UNICODE)."\n";
        }
    }
}

foreach (['8990', '8995', '8997', '8975', '8815'] as $ref) {
    dumpRef($rows, $ref);
}
