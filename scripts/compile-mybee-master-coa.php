<?php

/**
 * Compiles the My Bee master COA workbook into a PHP catalog.
 * Source of truth: Modules/Accounting/data/MyBee_Master_Chart_of_Accounts_Tree_v6.xlsx
 *
 * Level 1 roots are conceptual; level 2 becomes account subtypes; levels 3–5 become
 * chart accounts (including workbook example leaves such as Customer 1 / Bank Account 1).
 */

declare(strict_types=1);

use Modules\Accounting\Support\MyBeeMasterCoaRules;
use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';

$repoXlsx = __DIR__.'/../Modules/Accounting/data/MyBee_Master_Chart_of_Accounts_Tree_v6.xlsx';
$downloadXlsx = 'C:/Users/ASUS/Downloads/MyBee_Master_Chart_of_Accounts_Tree_v6.xlsx';
$path = is_file($repoXlsx) ? $repoXlsx : $downloadXlsx;

if (! is_file($path)) {
    fwrite(STDERR, "Workbook not found: {$path}\n");
    exit(1);
}

$sheet = IOFactory::load($path)->getSheet(0);
$types = [];
$accounts = [];
$maxLevel = 0;

for ($r = 2; $r <= (int) $sheet->getHighestRow(); $r++) {
    $gl = trim((string) $sheet->getCell('A'.$r)->getFormattedValue());
    if ($gl === '') {
        continue;
    }

    $nameAr = MyBeeMasterCoaRules::cleanName((string) $sheet->getCell('B'.$r)->getValue());
    $nameEn = MyBeeMasterCoaRules::cleanName((string) $sheet->getCell('C'.$r)->getValue());
    $level = (int) $sheet->getCell('D'.$r)->getValue();
    $parentGl = trim((string) $sheet->getCell('E'.$r)->getFormattedValue());
    $classEn = MyBeeMasterCoaRules::cleanName((string) $sheet->getCell('G'.$r)->getValue());
    $natureRaw = trim((string) $sheet->getCell('H'.$r)->getValue());
    $postingRaw = trim((string) $sheet->getCell('I'.$r)->getValue());
    $sector = MyBeeMasterCoaRules::cleanName((string) $sheet->getCell('J'.$r)->getValue());
    $maxLevel = max($maxLevel, $level);

    $row = [
        'gl_code' => $gl,
        'name_ar' => $nameAr,
        'name_en' => $nameEn,
        'level' => $level,
        'parent_gl' => $parentGl === '' ? null : $parentGl,
        'account_primary_type' => MyBeeMasterCoaRules::primaryTypeFromClass($classEn),
        'normal_balance' => MyBeeMasterCoaRules::normalBalance($natureRaw),
        'allow_direct_posting' => MyBeeMasterCoaRules::allowsPosting($postingRaw),
        'sector' => $sector === '' ? null : $sector,
    ];

    if ($level <= 1) {
        continue;
    }

    if ($level === 2) {
        $types[] = [
            'gl_code' => $row['gl_code'],
            'name_ar' => $row['name_ar'],
            'name_en' => $row['name_en'],
            'account_primary_type' => $row['account_primary_type'],
        ];
        continue;
    }

    $accounts[] = $row;
}

$catalog = [
    'version' => 'v6',
    'pack' => 'mybee_master',
    'source' => 'MyBee_Master_Chart_of_Accounts_Tree_v6.xlsx',
    'max_level' => $maxLevel,
    'types' => $types,
    'accounts' => $accounts,
    'account_categories' => MyBeeMasterCoaRules::accountCategories(),
    'routing_gl_candidates' => MyBeeMasterCoaRules::routingGlCandidates(),
    'color_system' => MyBeeMasterCoaRules::colorSystem(),
];

$out = __DIR__.'/../Modules/Accounting/data/mybee-master-coa-v6.php';
$export = var_export($catalog, true);
$php = <<<PHP
<?php

/**
 * Compiled My Bee master chart of accounts (v6).
 * Regenerate: php scripts/compile-mybee-master-coa.php
 */

return {$export};

PHP;

file_put_contents($out, $php);

echo 'types='.count($types).' accounts='.count($accounts).' max_level='.$maxLevel.PHP_EOL;
echo 'wrote '.$out.PHP_EOL;
