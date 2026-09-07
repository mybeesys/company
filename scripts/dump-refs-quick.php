<?php

require __DIR__.'/../vendor/autoload.php';

use Modules\Accounting\Services\JournalEntry\JournalTransactionsExcelParser;
use PhpOffice\PhpSpreadsheet\IOFactory;

$targets = ['9128', '9129', '9131'];
$files = [
    'C:/Users/ASUS/Downloads/Journal Transactions (1) (1).xls',
    'C:/Users/ASUS/Downloads/Journal Transactions (1).xls',
    'C:/Users/ASUS/Downloads/journal-supplement-2026-missing-entries.xlsx',
];

foreach ($files as $path) {
    echo "=== ".basename($path)." ===\n";
    if (! is_file($path)) {
        echo "NOT FOUND\n\n";
        continue;
    }

    try {
        $ss = IOFactory::load($path);
        $sheet = $ss->getSheetByName('Journal Transactions (2)') ?? $ss->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        $found = false;
        foreach ($rows as $rowNum => $row) {
            $vals = array_values($row);
            $a = ltrim(trim((string) ($vals[0] ?? '')), '0') ?: '0';
            $b = ltrim(trim((string) ($vals[1] ?? '')), '0') ?: '0';
            if (in_array($a, $targets, true) || in_array($b, $targets, true)) {
                $found = true;
                echo "R$rowNum: ".json_encode(array_slice($vals, 0, 8), JSON_UNESCAPED_UNICODE)."\n";
            }
        }
        if (! $found) {
            echo "Refs not present in raw rows.\n";
        }

        $parsed = (new JournalTransactionsExcelParser)->parse($path);
        $refs = array_column($parsed['entries'], 'ref_no');
        $max = $refs !== [] ? max(array_map('intval', $refs)) : 0;
        $min = $refs !== [] ? min(array_map('intval', $refs)) : 0;
        echo 'Parsed entries: '.count($parsed['entries'])." | ref range: $min-$max\n";
        foreach ($targets as $t) {
            echo in_array($t, $refs, true) ? "  $t: IN PARSER\n" : "  $t: MISSING from parser\n";
        }
    } catch (Throwable $e) {
        echo 'Error: '.$e->getMessage()."\n";
    }
    echo "\n";
}

// Also scan Downloads for any xls mentioning these refs
$dl = 'C:/Users/ASUS/Downloads';
foreach (glob($dl.'/*.{xls,xlsx}', GLOB_BRACE) ?: [] as $f) {
    $name = basename($f);
    if (! preg_match('/journal|قيد|استاذ|أستاذ|trial|ميزان/iu', $name)) {
        continue;
    }
    // quick binary/string search
    $bin = @file_get_contents($f, false, null, 0, 5_000_000);
    if ($bin === false) {
        continue;
    }
    $hit = [];
    foreach ($targets as $t) {
        if (str_contains($bin, $t)) {
            $hit[] = $t;
        }
    }
    if ($hit !== []) {
        echo "File may contain refs [".implode(',', $hit)."]: $name\n";
    }
}
