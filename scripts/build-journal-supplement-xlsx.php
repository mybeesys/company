<?php

declare(strict_types=1);

/**
 * Build supplemental journal import for 5 missing refs (514 + 215001 gaps).
 *
 * Usage:
 *   php scripts/build-journal-supplement-xlsx.php [output.xlsx]
 */

require __DIR__.'/../vendor/autoload.php';

use Modules\Accounting\Services\JournalEntry\JournalTransactionsExcelParser;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$output = $argv[1] ?? __DIR__.'/../docs/imports/journal-supplement-2026-missing-entries.xlsx';

$entries = [
    [
        'ref' => '9043',
        'date' => '30/06/2026',
        'lines' => [
            ['215001', 'القيمة المضافة المحصلة', 'اقفال القيمة المضافة المحصلة عن شهر يونيو 2026', '653308.18', '0'],
            ['2214', 'جاري ضريبة القيمة المضافة', 'اقفال القيمة المضافة المحصلة عن شهر يونيو 2026', '0', '653308.18'],
        ],
    ],
    [
        'ref' => '9014',
        'date' => '07/07/2026',
        'lines' => [
            ['514', 'مصروف رواتب تشغيلية', "راتب 4 ايام - Mohamed Refaat\nElbastawisy", '2204', '0'],
            ['1202001', 'حساب حاضنات الاعمال الرئيسي 74100002443500', "راتب 4 ايام - Mohamed Refaat\nElbastawisy", '0', '2204'],
        ],
    ],
    [
        'ref' => '9034',
        'date' => '16/07/2026',
        'lines' => [
            ['514', 'مصروف رواتب تشغيلية', "راتب اغسطس 26 - Mamdouh Awad\nAlanazi", '8350', '0'],
            ['5209', 'مصروف التأمينات الاجتماعية', "التأمينات الاجتماعية اغسطس 26 - Mamdouh Awad Alanazi\n", '0', '762.45'],
            ['1202001', 'حساب حاضنات الاعمال الرئيسي 74100002443500', "راتب اغسطس 26 - Mamdouh Awad\nAlanazi", '0', '7587.55'],
        ],
    ],
    [
        'ref' => '9035',
        'date' => '16/07/2026',
        'lines' => [
            ['514', 'مصروف رواتب تشغيلية', 'راتب 3 ايام -  Abubakar Sadiq Mohammad', '1203', '0'],
            ['1202001', 'حساب حاضنات الاعمال الرئيسي 74100002443500', 'راتب 3 ايام -  Abubakar Sadiq Mohammad', '0', '1203'],
        ],
    ],
    [
        'ref' => '9038',
        'date' => '30/07/2026',
        'lines' => [
            ['514', 'مصروف رواتب تشغيلية', "راتب يوليو 26 - Haval Abdulaziz Latif\n", '14330', '0'],
            ['514', 'مصروف رواتب تشغيلية', "راتب يوليو 26 - Corey John Scott\n", '14630', '0'],
            ['1202001', 'حساب حاضنات الاعمال الرئيسي 74100002443500', 'Manual Local Transfers - July 2026', '0', '28960'],
        ],
    ],
];

$rows = [
    ['التاريخ', 'رقم', 'الحساب', 'كود الحساب', 'الوصف', 'المصدر', 'مدين', 'دائن'],
];

foreach ($entries as $entry) {
    $rows[] = [$entry['ref'], '', '', '', '', '', '', ''];

    $debitTotal = 0.0;
    $creditTotal = 0.0;

    foreach ($entry['lines'] as [$gl, $name, $desc, $debit, $credit]) {
        $debitVal = (float) $debit;
        $creditVal = (float) $credit;
        $debitTotal += $debitVal;
        $creditTotal += $creditVal;

        $rows[] = [
            $entry['date'],
            $entry['ref'],
            $name,
            $gl,
            $desc,
            'قيد يدوي',
            $debit !== '0' ? $debit : '0',
            $credit !== '0' ? $credit : '0',
        ];
    }

    $debitFmt = number_format($debitTotal, 2, '.', '');
    $creditFmt = number_format($creditTotal, 2, '.', '');

    $rows[] = ['', '', '', '', '', '', $debitFmt, $creditFmt];
    $rows[] = ['المجموع', '', '', '', '', '', $debitFmt, $creditFmt];
}

$spreadsheet = new Spreadsheet;
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Journal Transactions (2)');

foreach ($rows as $rowIndex => $row) {
    foreach ($row as $colIndex => $value) {
        $coordinate = $sheet->getCellByColumnAndRow($colIndex + 1, $rowIndex + 1)->getCoordinate();
        // Keep dates as literal strings (avoid Excel serial reinterpretation).
        if ($colIndex === 0 && $rowIndex > 0 && is_string($value) && str_contains($value, '/')) {
            $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_STRING);
        } else {
            $sheet->setCellValue($coordinate, $value);
        }
    }
}

$dir = dirname($output);
if (! is_dir($dir)) {
    mkdir($dir, 0777, true);
}

(new Xlsx($spreadsheet))->save($output);

$parsed = (new JournalTransactionsExcelParser)->parse($output);

$summary = [
    'output' => $output,
    'entries_count' => count($parsed['entries']),
    'errors_count' => count($parsed['errors']),
    'errors' => $parsed['errors'],
    'entries' => array_map(function (array $entry) {
        $debit = array_sum(array_map(fn ($l) => (float) $l['debit'], $entry['lines']));
        $credit = array_sum(array_map(fn ($l) => (float) $l['credit'], $entry['lines']));

        return [
            'ref_no' => $entry['ref_no'],
            'operation_date' => $entry['operation_date'],
            'lines' => count($entry['lines']),
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
        ];
    }, $parsed['entries']),
    'totals' => [
        'debit' => round(array_sum(array_map(function (array $entry) {
            return array_sum(array_map(fn ($l) => (float) $l['debit'], $entry['lines']));
        }, $parsed['entries'])), 2),
        'credit' => round(array_sum(array_map(function (array $entry) {
            return array_sum(array_map(fn ($l) => (float) $l['credit'], $entry['lines']));
        }, $parsed['entries'])), 2),
    ],
];

echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;

if ($parsed['errors'] !== [] || count($parsed['entries']) !== 5) {
    exit(1);
}
