<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Color;

$path = $argv[1] ?? '';
if ($path === '' || ! is_file($path)) {
    fwrite(STDERR, "Usage: php inspect-trial-balance-xlsx.php <file.xlsx>\n");
    exit(1);
}

$wb = IOFactory::load($path);
$ws = $wb->getActiveSheet();
$maxRow = $ws->getHighestRow();
$maxCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($ws->getHighestColumn());

echo "Sheet: {$ws->getTitle()}\n";
echo "Rows: {$maxRow}, Cols: {$maxCol}\n\n";

for ($r = 1; $r <= min(8, $maxRow); $r++) {
    $cells = [];
    for ($c = 1; $c <= $maxCol; $c++) {
        $cells[] = $ws->getCellByColumnAndRow($c, $r)->getFormattedValue();
    }
    echo "R{$r}: ".json_encode($cells, JSON_UNESCAPED_UNICODE)."\n";
}

echo "\n--- Yellow / highlighted rows ---\n";
$yellowRows = [];
for ($r = 1; $r <= $maxRow; $r++) {
    $isYellow = false;
    for ($c = 1; $c <= $maxCol; $c++) {
        $fill = $ws->getStyleByColumnAndRow($c, $r)->getFill();
        $rgb = $fill->getStartColor()->getRGB();
        $argb = $fill->getStartColor()->getARGB();
        if ($rgb && $rgb !== 'FFFFFF' && $rgb !== '000000' && $fill->getFillType() !== 'none') {
            $isYellow = true;
            break;
        }
        // common yellow shades
        if (in_array(strtoupper($rgb), ['FFFF00', 'FFF2CC', 'FFE699', 'FFD966', 'FFEB9C', 'FFFACD'], true)) {
            $isYellow = true;
            break;
        }
    }
    if ($isYellow) {
        $cells = [];
        for ($c = 1; $c <= $maxCol; $c++) {
            $cells[] = $ws->getCellByColumnAndRow($c, $r)->getFormattedValue();
        }
        $yellowRows[] = ['row' => $r, 'cells' => $cells];
    }
}

echo 'Count: '.count($yellowRows)."\n";
foreach (array_slice($yellowRows, 0, 40) as $item) {
    echo "R{$item['row']}: ".json_encode($item['cells'], JSON_UNESCAPED_UNICODE)."\n";
}

if (count($yellowRows) > 40) {
    echo '... and '.(count($yellowRows) - 40)." more\n";
}
