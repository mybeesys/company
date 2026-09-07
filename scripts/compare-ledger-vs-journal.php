<?php

require __DIR__.'/../vendor/autoload.php';

use Carbon\Carbon;
use Modules\Accounting\Services\JournalEntry\JournalTransactionsExcelParser;
use PhpOffice\PhpSpreadsheet\IOFactory;

$ledger514 = $argv[1] ?? 'C:/Users/ASUS/Downloads/6a95817714f25-حساب الأستاذ.xls';
$ledger215 = $argv[2] ?? 'C:/Users/ASUS/Downloads/6a9581bfed2ad-حساب الأستاذ.xls';
$journalFile = $argv[3] ?? 'C:/Users/ASUS/Downloads/Journal Transactions (1).xls';
$startDate = '2026-01-01';
$endDate = '2026-07-31';

function parseLedger(string $path, string $expectedGl): array
{
    $sheet = IOFactory::load($path)->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);

    $accountLabel = '';
    $openingDebit = 0.0;
    $openingCredit = 0.0;
    $lines = [];

    foreach ($rows as $rowNum => $row) {
        $vals = array_values($row);
        $a = trim((string) ($vals[0] ?? ''));
        $ref = trim((string) ($vals[1] ?? ''));
        $dateRaw = trim((string) ($vals[3] ?? ''));
        $desc = trim((string) ($vals[5] ?? ''));
        $debit = normalizeAmount($vals[6] ?? null);
        $credit = normalizeAmount($vals[7] ?? null);

        if ($rowNum <= 2) {
            continue;
        }

        if ($accountLabel === '' && $a !== '' && ! is_numeric($a) && $a !== 'الرصيد قبل') {
            $accountLabel = $a;
        }

        if ($a === 'الرصيد قبل') {
            $openingDebit = normalizeAmount($vals[8] ?? $vals[6] ?? 0);
            $openingCredit = normalizeAmount($vals[9] ?? $vals[7] ?? 0);

            continue;
        }

        if ($ref === '' || $dateRaw === '') {
            continue;
        }

        $date = parseLedgerDate($dateRaw);
        if ($date === null) {
            continue;
        }

        $lines[] = [
            'row' => (int) $rowNum,
            'line_no' => $a,
            'ref_no' => ltrim($ref, '0') !== '' ? ltrim($ref, '0') : '0',
            'transfer_id' => trim((string) ($vals[2] ?? '')),
            'date' => $date,
            'description' => $desc,
            'debit' => $debit,
            'credit' => $credit,
        ];
    }

    preg_match('/(\d+)\s*$/', $accountLabel, $m);
    $glFromLabel = $m[1] ?? $expectedGl;

    return [
        'path' => $path,
        'account_label' => $accountLabel,
        'gl_code' => $glFromLabel,
        'opening_debit' => round($openingDebit, 2),
        'opening_credit' => round($openingCredit, 2),
        'lines' => $lines,
    ];
}

function parseLedgerDate(string $raw): ?string
{
    $raw = trim(str_replace('\\/', '/', $raw));
    foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y'] as $fmt) {
        try {
            return Carbon::createFromFormat($fmt, $raw)->format('Y-m-d');
        } catch (\Throwable) {
        }
    }

    return null;
}

function normalizeAmount(mixed $raw): float
{
    if ($raw === null || $raw === '') {
        return 0.0;
    }
    $s = str_replace([',', ' '], '', trim((string) $raw));

    return is_numeric($s) ? round((float) $s, 2) : 0.0;
}

function summarizeLedger(array $ledger, string $start, string $end): array
{
    $inPeriod = array_values(array_filter(
        $ledger['lines'],
        fn (array $l) => $l['date'] >= $start && $l['date'] <= $end
    ));
    $outside = array_values(array_filter(
        $ledger['lines'],
        fn (array $l) => $l['date'] < $start || $l['date'] > $end
    ));

    $debit = round(array_sum(array_column($inPeriod, 'debit')), 2);
    $credit = round(array_sum(array_column($inPeriod, 'credit')), 2);

    $refs = [];
    foreach ($inPeriod as $l) {
        $refs[$l['ref_no']][] = $l;
    }

    return [
        'gl_code' => $ledger['gl_code'],
        'account_label' => $ledger['account_label'],
        'opening_debit' => $ledger['opening_debit'],
        'opening_credit' => $ledger['opening_credit'],
        'total_lines' => count($ledger['lines']),
        'period_lines' => count($inPeriod),
        'outside_period_lines' => count($outside),
        'period_debit' => $debit,
        'period_credit' => $credit,
        'period_net' => round($debit - $credit, 2),
        'unique_refs_in_period' => count($refs),
        'date_min' => $inPeriod !== [] ? min(array_column($inPeriod, 'date')) : null,
        'date_max' => $inPeriod !== [] ? max(array_column($inPeriod, 'date')) : null,
        'refs' => $refs,
        'in_period' => $inPeriod,
    ];
}

function journalGlTotals(string $path, string $gl, string $start, string $end): array
{
    $parser = new JournalTransactionsExcelParser;
    $entries = $parser->parse($path)['entries'];
    $debit = 0.0;
    $credit = 0.0;
    $refs = [];

    foreach ($entries as $entry) {
        if ($entry['operation_date'] < $start || $entry['operation_date'] > $end) {
            continue;
        }
        foreach ($entry['lines'] as $line) {
            if ($line['gl_code'] !== $gl) {
                continue;
            }
            $debit += (float) $line['debit'];
            $credit += (float) $line['credit'];
            $refs[$entry['ref_no']][] = $line;
        }
    }

    return [
        'period_debit' => round($debit, 2),
        'period_credit' => round($credit, 2),
        'period_net' => round($debit - $credit, 2),
        'unique_refs' => count($refs),
        'refs' => $refs,
    ];
}

function compareRefs(array $ledgerRefs, array $journalRefs): array
{
    $ledgerSet = array_keys($ledgerRefs);
    $journalSet = array_keys($journalRefs);
    sort($ledgerSet);
    sort($journalSet);

    $onlyLedger = array_values(array_diff($ledgerSet, $journalSet));
    $onlyJournal = array_values(array_diff($journalSet, $ledgerSet));
    $common = array_values(array_intersect($ledgerSet, $journalSet));

    $amountMismatches = [];
    foreach ($common as $ref) {
        $lDebit = round(array_sum(array_column($ledgerRefs[$ref], 'debit')), 2);
        $lCredit = round(array_sum(array_column($ledgerRefs[$ref], 'credit')), 2);
        $jDebit = round(array_sum(array_map(fn ($x) => (float) $x['debit'], $journalRefs[$ref])), 2);
        $jCredit = round(array_sum(array_map(fn ($x) => (float) $x['credit'], $journalRefs[$ref])), 2);
        if (abs($lDebit - $jDebit) >= 0.01 || abs($lCredit - $jCredit) >= 0.01) {
            $amountMismatches[] = [
                'ref_no' => $ref,
                'ledger_debit' => $lDebit,
                'ledger_credit' => $lCredit,
                'journal_debit' => $jDebit,
                'journal_credit' => $jCredit,
                'debit_diff' => round($lDebit - $jDebit, 2),
                'credit_diff' => round($lCredit - $jCredit, 2),
            ];
        }
    }

    usort($amountMismatches, fn ($a, $b) => abs($b['debit_diff']) <=> abs($a['debit_diff']));

    return [
        'common_refs' => count($common),
        'only_in_ledger' => $onlyLedger,
        'only_in_journal' => $onlyJournal,
        'amount_mismatches' => $amountMismatches,
    ];
}

$ledger514Data = parseLedger($ledger514, '514');
$ledger215Data = parseLedger($ledger215, '215001');

$sum514 = summarizeLedger($ledger514Data, $startDate, $endDate);
$sum215 = summarizeLedger($ledger215Data, $startDate, $endDate);

$journal514 = journalGlTotals($journalFile, '514', $startDate, $endDate);
$journal215001 = journalGlTotals($journalFile, '215001', $startDate, $endDate);
$journal215002 = journalGlTotals($journalFile, '215002', $startDate, $endDate);

$cmp514 = compareRefs($sum514['refs'], $journal514['refs']);

// If second file is 215002, compare both
$gl215 = $sum215['gl_code'];
$journal215 = $gl215 === '215002' ? $journal215002 : $journal215001;
$cmp215 = compareRefs($sum215['refs'], $journal215['refs']);

// Trial balance expected values
$bee514 = ['debit' => 20676673.24, 'credit' => 97154.74];
$ext514 = ['debit' => 20717390.24, 'credit' => 97154.74];
$bee215001 = ['debit' => 3265622.23, 'credit' => 4552295.07];
$ext215001 = ['debit' => 3918930.41, 'credit' => 4552295.10];

// Sum only-in-ledger refs amounts for 514
$onlyLedger514Totals = ['debit' => 0.0, 'credit' => 0.0, 'details' => []];
foreach ($cmp514['only_in_ledger'] as $ref) {
    $d = round(array_sum(array_column($sum514['refs'][$ref], 'debit')), 2);
    $c = round(array_sum(array_column($sum514['refs'][$ref], 'credit')), 2);
    $onlyLedger514Totals['debit'] += $d;
    $onlyLedger514Totals['credit'] += $c;
    $onlyLedger514Totals['details'][] = compact('ref', 'd', 'c');
}

usort($onlyLedger514Totals['details'], fn ($a, $b) => $b['d'] <=> $a['d']);

echo json_encode([
    'period' => [$startDate, $endDate],
    'account_514' => [
        'ledger' => [
            'label' => $sum514['account_label'],
            'opening' => ['debit' => $sum514['opening_debit'], 'credit' => $sum514['opening_credit']],
            'period' => ['debit' => $sum514['period_debit'], 'credit' => $sum514['period_credit'], 'net' => $sum514['period_net']],
            'lines' => $sum514['period_lines'],
            'unique_refs' => $sum514['unique_refs_in_period'],
            'date_range' => [$sum514['date_min'], $sum514['date_max']],
        ],
        'journal_export' => $journal514,
        'trial_balance' => ['bee' => $bee514, 'external' => $ext514],
        'ledger_vs_journal' => [
            'debit_diff' => round($sum514['period_debit'] - $journal514['period_debit'], 2),
            'credit_diff' => round($sum514['period_credit'] - $journal514['period_credit'], 2),
        ],
        'ledger_vs_bee_tb' => [
            'debit_diff' => round($sum514['period_debit'] - $bee514['debit'], 2),
            'credit_diff' => round($sum514['period_credit'] - $bee514['credit'], 2),
        ],
        'ledger_vs_ext_tb' => [
            'debit_diff' => round($sum514['period_debit'] - $ext514['debit'], 2),
            'credit_diff' => round($sum514['period_credit'] - $ext514['credit'], 2),
        ],
        'ref_compare' => [
            'common' => $cmp514['common_refs'],
            'only_in_ledger_count' => count($cmp514['only_in_ledger']),
            'only_in_journal_count' => count($cmp514['only_in_journal']),
            'only_in_ledger_totals' => [
                'debit' => round($onlyLedger514Totals['debit'], 2),
                'credit' => round($onlyLedger514Totals['credit'], 2),
            ],
            'only_in_ledger_top' => array_slice($onlyLedger514Totals['details'], 0, 20),
            'only_in_ledger_refs' => $cmp514['only_in_ledger'],
            'only_in_journal_refs' => $cmp514['only_in_journal'],
            'amount_mismatches_top' => array_slice($cmp514['amount_mismatches'], 0, 15),
        ],
    ],
    'account_215_file' => [
        'note' => 'Second file header says: '.$sum215['account_label'],
        'actual_gl' => $gl215,
        'ledger' => [
            'opening' => ['debit' => $sum215['opening_debit'], 'credit' => $sum215['opening_credit']],
            'period' => ['debit' => $sum215['period_debit'], 'credit' => $sum215['period_credit'], 'net' => $sum215['period_net']],
            'lines' => $sum215['period_lines'],
            'unique_refs' => $sum215['unique_refs_in_period'],
        ],
        'journal_export_'.$gl215 => $journal215,
        'trial_balance_215001' => ['bee' => $bee215001, 'external' => $ext215001],
        'ledger_vs_journal' => [
            'debit_diff' => round($sum215['period_debit'] - $journal215['period_debit'], 2),
            'credit_diff' => round($sum215['period_credit'] - $journal215['period_credit'], 2),
        ],
        'ref_compare' => [
            'common' => $cmp215['common_refs'],
            'only_in_ledger_count' => count($cmp215['only_in_ledger']),
            'only_in_journal_count' => count($cmp215['only_in_journal']),
            'only_in_ledger_refs' => array_slice($cmp215['only_in_ledger'], 0, 30),
            'only_in_journal_refs' => array_slice($cmp215['only_in_journal'], 0, 30),
            'amount_mismatches_top' => array_slice($cmp215['amount_mismatches'], 0, 15),
        ],
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
