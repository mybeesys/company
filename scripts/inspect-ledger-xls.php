<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function loadSheet(string $path): array
{
    if (! is_file($path)) {
        throw new RuntimeException("File not found: {$path}");
    }

    $spreadsheet = IOFactory::load($path);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);

    return [
        'title' => $sheet->getTitle(),
        'rows' => $rows,
        'highest_row' => (int) $sheet->getHighestRow(),
    ];
}

function normalizeAmount(mixed $raw): float
{
    if ($raw === null || $raw === '') {
        return 0.0;
    }
    $s = str_replace([',', ' '], '', trim((string) $raw));

    return is_numeric($s) ? round((float) $s, 2) : 0.0;
}

function normalizeGl(mixed $raw): string
{
    $s = trim((string) $raw);
    $s = preg_replace('/[\x{FEFF}"\s]/u', '', $s) ?? $s;
    $s = str_replace(',', '', $s);
    if (preg_match('/^(\d+)\.0+$/', $s, $m)) {
        $s = $m[1];
    }

    return $s;
}

function printPreview(array $data, int $max = 15): void
{
    $rows = $data['rows'];
    echo "Sheet: {$data['title']} | Rows: {$data['highest_row']}\n";
    $count = 0;
    foreach ($rows as $rowNum => $row) {
        if ($count >= $max) {
            break;
        }
        $vals = array_values($row);
        $nonEmpty = array_filter($vals, fn ($v) => trim((string) $v) !== '');
        if ($nonEmpty === []) {
            continue;
        }
        echo "R{$rowNum}: ".json_encode(array_slice($vals, 0, 12), JSON_UNESCAPED_UNICODE)."\n";
        $count++;
    }
    echo "\n";
}

$files = [
    '514' => $argv[1] ?? 'C:/Users/ASUS/Downloads/6a95817714f25-حساب الأستاذ.xls',
    '215001' => $argv[2] ?? 'C:/Users/ASUS/Downloads/6a9581bfed2ad-حساب الأستاذ.xls',
];

foreach ($files as $label => $path) {
    echo "========== ACCOUNT {$label} ==========\n";
    echo "File: {$path}\n";
    $data = loadSheet($path);
    printPreview($data, 20);
}
