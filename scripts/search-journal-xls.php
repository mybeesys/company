<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? __DIR__.'/../storage/tenanttest/app/journal-import/747dbb80-ebd1-4a53-a506-79588630df17.xls';
$wb = IOFactory::load($path);
$ws = $wb->getActiveSheet();
$hits = [];
for ($r = 1; $r <= $ws->getHighestRow(); $r++) {
    $row = [];
    for ($c = 1; $c <= 12; $c++) {
        $row[] = trim((string) $ws->getCellByColumnAndRow($c, $r)->getValue());
    }
    $line = implode('|', $row);
    if (preg_match('/\b(31|221|222)\b/', $line) || preg_match('/214377|314377|164377|50000|276606/', $line)) {
        if (preg_match('/^31$|^221$|^222$|214377|314377|164377|276606/', $row[3] ?? '') || preg_match('/214377|314377|164377|276606/', $line)) {
            $hits[] = "R{$r}: {$line}";
        }
    }
}
echo "File: {$path}\nHits: ".count($hits)."\n";
foreach (array_slice($hits, 0, 50) as $h) {
    echo $h."\n";
}
