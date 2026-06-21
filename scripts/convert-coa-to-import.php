<?php

/**
 * One-off: convert American Academy chart_of_accounts export to Bee tree-of-accounts import format.
 *
 * Usage:
 *   php scripts/convert-coa-to-import.php [source.xls] [output.xlsx]
 */

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function normalizeGlCode(mixed $raw): string
{
    $s = trim((string) $raw);
    if ($s === '') {
        return '';
    }

    $s = preg_replace('/[\x{FEFF}"\s]/u', '', $s) ?? $s;
    $s = str_replace(',', '', $s);

    if (preg_match('/^(\d+)\.0+$/', $s, $m)) {
        $s = $m[1];
    }

    return $s;
}

function deriveParentGl(string $gl, array $allGls): ?string
{
    if (strlen($gl) <= 1) {
        return null;
    }

    for ($len = strlen($gl) - 1; $len >= 1; $len--) {
        $prefix = substr($gl, 0, $len);
        if (array_key_exists($prefix, $allGls)) {
            return $prefix;
        }
    }

    return null;
}

function mapPrimaryType(string $gl): string
{
    $first = substr($gl, 0, 1);

    return match ($first) {
        '1' => 'asset',
        '2' => 'liabilities',
        '3' => 'equity',
        '4' => 'income',
        '5' => 'expenses',
        default => 'asset',
    };
}

$source = $argv[1] ?? 'c:/Users/ASUS/Downloads/american_academy_co-Journal Accounts-chart_of_accounts (1).xls';
$output = $argv[2] ?? __DIR__.'/../docs/imports/american-academy-tree-of-accounts-import.xlsx';

if (! file_exists($source)) {
    fwrite(STDERR, "Source file not found: {$source}\n");
    exit(1);
}

$outputDir = dirname($output);
if (! is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$spreadsheet = IOFactory::load($source);
$rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

$accounts = [];
foreach ($rows as $i => $row) {
    if ((int) $i === 1) {
        continue;
    }

    $vals = array_values($row);
    $gl = normalizeGlCode($vals[0] ?? '');
    if ($gl === '') {
        continue;
    }

    $accounts[(string) $gl] = trim((string) ($vals[1] ?? ''));
}

if ($accounts === []) {
    fwrite(STDERR, "No accounts found in source file.\n");
    exit(1);
}

$importRows = [];
$missingParents = [];

foreach ($accounts as $gl => $name) {
    $gl = (string) $gl;
    $parent = deriveParentGl($gl, $accounts);
    if ($gl !== array_key_first($accounts) && $parent === null && strlen($gl) > 1) {
        $missingParents[] = $gl;
    }

    $importRows[] = [
        'gl_code' => $gl,
        'name_ar' => $name !== '' ? $name : $gl,
        'name_en' => $name !== '' ? $name : $gl,
        'account_primary_type' => mapPrimaryType($gl),
        'parent_gl_code' => $parent ?? '',
        'status' => 'active',
    ];
}

usort($importRows, fn (array $a, array $b) => strlen($a['gl_code']) <=> strlen($b['gl_code']));

if ($missingParents !== []) {
    fwrite(STDERR, 'Warning: accounts without derivable parent: '.implode(', ', $missingParents)."\n");
}

$out = new Spreadsheet;
$sheet = $out->getActiveSheet();
$sheet->setTitle('accounts_template');

$headings = ['gl_code', 'name_ar', 'name_en', 'account_primary_type', 'parent_gl_code', 'status'];
$sheet->fromArray($headings, null, 'A1');

$rowNum = 2;
foreach ($importRows as $row) {
    $sheet->fromArray(array_values($row), null, 'A'.$rowNum);
    $rowNum++;
}

foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

(new Xlsx($out))->save($output);

echo "Converted ".count($importRows)." accounts.\n";
echo "Output: {$output}\n";
