<?php
require __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
$r = IOFactory::load(__DIR__.'/../docs/imports/american-academy-tree-of-accounts-import.xlsx')->getActiveSheet()->toArray(null, true, true, true);
for ($i = 1; $i <= 8; $i++) {
    echo json_encode(array_values($r[$i] ?? []), JSON_UNESCAPED_UNICODE).PHP_EOL;
}
// find 53,530,531,53104,53048
foreach ($r as $row) {
    $vals = array_values($row);
    $gl = trim((string) ($vals[0] ?? ''));
    if (in_array($gl, ['53', '530', '531', '53104', '53048', '5'], true)) {
        echo "ROW: ".json_encode($vals, JSON_UNESCAPED_UNICODE).PHP_EOL;
    }
}
