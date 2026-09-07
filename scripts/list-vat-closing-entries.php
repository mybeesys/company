<?php

require __DIR__.'/../vendor/autoload.php';
use Carbon\Carbon;
use Modules\Accounting\Services\JournalEntry\JournalTransactionsExcelParser;
use PhpOffice\PhpSpreadsheet\IOFactory;

$ledgerPath = 'C:/Users/ASUS/Downloads/6a9581bfed2ad-حساب الأستاذ (1).xls';
$journalPath = 'C:/Users/ASUS/Downloads/Journal Transactions (1).xls';

function parseLedgerClosingEntries(string $path): array
{
    $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, true);
    $out = [];
    foreach ($rows as $row) {
        $vals = array_values($row);
        $ref = trim((string) ($vals[1] ?? ''));
        $dateRaw = trim(str_replace('\\/', '/', (string) ($vals[3] ?? '')));
        $desc = trim((string) ($vals[5] ?? ''));
        if ($ref === '' || ! str_contains($desc, 'اقفال')) {
            continue;
        }
        $debit = (float) str_replace(',', '', (string) ($vals[6] ?? 0));
        $credit = (float) str_replace(',', '', (string) ($vals[7] ?? 0));
        try {
            $date = Carbon::createFromFormat('d/m/Y', $dateRaw)->format('Y-m-d');
        } catch (\Throwable) {
            $date = $dateRaw;
        }
        $out[] = [
            'ref_no' => ltrim($ref, '0') ?: '0',
            'date' => $date,
            'description' => $desc,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
        ];
    }

    return $out;
}

$ledgerClosing = parseLedgerClosingEntries($ledgerPath);
$journalRefs = [];
foreach ((new JournalTransactionsExcelParser)->parse($journalPath)['entries'] as $e) {
    $journalRefs[$e['ref_no']] = $e;
}

$result = [];
foreach ($ledgerClosing as $row) {
    $ref = $row['ref_no'];
    $inJournal = isset($journalRefs[$ref]);
    $result[] = $row + [
        'in_journal_export' => $inJournal,
        'journal_has_215001_debit' => $inJournal
            ? round(array_sum(array_map(fn ($l) => $l['gl_code'] === '215001' ? (float) $l['debit'] : 0, $journalRefs[$ref]['lines'])), 2)
            : 0,
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
