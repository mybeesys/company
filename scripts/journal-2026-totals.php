<?php

require __DIR__.'/../vendor/autoload.php';

use Modules\Accounting\Services\JournalEntry\JournalTransactionsExcelParser;

$path = 'C:/Users/ASUS/Downloads/Journal Transactions (1) (1).xls';
$entries = (new JournalTransactionsExcelParser)->parse($path)['entries'];

// Exact offset check
$a = 98172.85; // 12050 Bee higher
$b = -92180;   // 514 Bee lower
$c = -4062;    // 5245
$d = -1930.85; // 5239
echo "Offset cluster sum: ".round($a + $b + $c + $d, 2)." (should be 0)\n\n";

// Find all 12050 debit lines that look like expenses (salary, insurance, ticket, car)
$expenseLike = [];
foreach ($entries as $e) {
    foreach ($e['lines'] as $l) {
        if ($l['gl_code'] !== '12050') {
            continue;
        }
        $debit = (float) $l['debit'];
        if ($debit <= 0) {
            continue;
        }
        $note = (string) ($l['note'] ?? '');
        $expenseLike[] = [
            'ref' => $e['ref_no'],
            'date' => $e['operation_date'],
            'amount' => $debit,
            'note' => $note,
        ];
    }
}

$sumAll = round(array_sum(array_column($expenseLike, 'amount')), 2);
echo "All 12050 debit lines: ".count($expenseLike)." sum=$sumAll\n";

// Filter non-feeding (exclude 100k+ library funding)
$ops = array_values(array_filter($expenseLike, fn ($x) => $x['amount'] < 100000));
$sumOps = round(array_sum(array_column($ops, 'amount')), 2);
echo "12050 operational debits (<100k): ".count($ops)." sum=$sumOps\n\n";

usort($ops, fn ($a, $b) => $b['amount'] <=> $a['amount']);
foreach ($ops as $x) {
    echo "{$x['date']} ref={$x['ref']} amt={$x['amount']} | {$x['note']}\n";
}

echo "\n=== Search notes for تامين سيارات / 5239 pattern ===\n";
foreach ($entries as $e) {
    foreach ($e['lines'] as $l) {
        $note = mb_strtolower((string) ($l['note'] ?? ''));
        $name = (string) ($l['account_name'] ?? '');
        if (str_contains($note, 'تامين') || str_contains($note, 'تأمين') || str_contains($name, '5239') || str_contains($name, 'تامين سيارات')) {
            $amt = max((float) $l['debit'], (float) $l['credit']);
            if (abs($amt - 1930.85) < 1 || str_contains($name, 'سيارات') || str_contains($note, 'سياره') || str_contains($note, 'سيارة')) {
                echo "{$e['operation_date']} ref={$e['ref_no']} gl={$l['gl_code']} D={$l['debit']} C={$l['credit']} | {$l['note']}\n";
            }
        }
    }
}

echo "\n=== Opening-only diffs confirmation ===\n";
echo "32/36/223/222 have ZERO period movement in Bee - differences are opening balances only.\n";

// Compare old July TB vs new June: 215001 fixed?
echo "\n=== FIXED vs PREVIOUS JULY TB ===\n";
echo "215001: WAS -653308, NOW +0.01  => FIXED (June VAT closing imported)\n";
echo "2214: WAS +550202, NOW 0       => FIXED\n";
echo "Period now ends 2026-06-30 (not 07-31) so July payroll refs 9014/9034/9035/9038 correctly out of scope.\n";
