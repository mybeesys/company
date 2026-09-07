<?php

require __DIR__.'/../vendor/autoload.php';

use Modules\Accounting\Services\JournalEntry\JournalTransactionsExcelParser;

$files = [
    '2026 H1' => 'C:/Users/ASUS/Downloads/Journal Transactions (1) (1).xls',
    '2026 to Jul' => 'C:/Users/ASUS/Downloads/Journal Transactions (1).xls',
];

foreach ($files as $label => $path) {
    if (! is_file($path)) {
        continue;
    }
    $entries = (new JournalTransactionsExcelParser)->parse($path)['entries'];
    $byMonth = [];
    $last = null;
    foreach ($entries as $e) {
        $m = substr($e['operation_date'], 0, 7);
        $byMonth[$m] = ($byMonth[$m] ?? 0) + 1;
        if ($last === null || (int) $e['ref_no'] > (int) $last['ref_no']) {
            $last = $e;
        }
    }
    ksort($byMonth);
    echo "$label: count=".count($entries)." last_ref={$last['ref_no']} last_date={$last['operation_date']}\n";
    echo '  months: '.json_encode($byMonth)."\n";
}

echo "\nInterpretation:\n";
echo "- Imported max ref ~9044 dated 2026-06-30\n";
echo "- Accountant cites 9128, 9129, 9131 => ~84+ vouchers AFTER export cutoff\n";
echo "- These are almost certainly July 2026 (or later) live entries never included in Journal Transactions export\n";
echo "- From his POV: Bee TB missing those vouchers => 'errors / incomplete'\n";
echo "- From our POV: we imported exactly what he exported; those refs were never in the file\n";
