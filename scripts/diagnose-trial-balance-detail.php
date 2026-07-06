<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

tenancy()->initialize(Tenant::find($argv[1] ?? 'test'));

$start = '2025-01-01';
$end = '2025-12-31';

foreach (['31', '221', '222'] as $gl) {
    $accountId = DB::table('accounting_accounts')->where('gl_code', $gl)->value('id');
    echo "\n######## GL {$gl} ALL TRANSACTIONS ########\n";
    $rows = DB::table('accounting_accounts_transactions as t')
        ->leftJoin('accounting_acc_trans_mappings as m', 'm.id', '=', 't.acc_trans_mapping_id')
        ->where('t.accounting_account_id', $accountId)
        ->orderBy('t.operation_date')
        ->orderBy('t.id')
        ->get(['t.operation_date', 't.type', 't.amount', 't.sub_type', 'm.ref_no', 'm.note']);

    foreach ($rows as $r) {
        echo "{$r->operation_date} | {$r->type} {$r->amount} | {$r->sub_type} | {$r->ref_no} | ".mb_substr((string) $r->note, 0, 50)."\n";
    }
}

// Simulate OLD trial balance (no opening_balance on start date)
echo "\n######## OLD vs NEW trial balance logic ########\n";
$openingSqlOld = 'DATE(t.operation_date) < ?';
$openingSqlNew = "(DATE(t.operation_date) < ? OR (DATE(t.operation_date) = ? AND t.sub_type = 'opening_balance'))";
$periodSqlOld = 'DATE(t.operation_date) >= ? AND DATE(t.operation_date) <= ?';
$periodSqlNew = "(DATE(t.operation_date) >= ? AND DATE(t.operation_date) <= ? AND NOT (DATE(t.operation_date) = ? AND t.sub_type = 'opening_balance'))";

foreach (['31', '221', '222'] as $gl) {
    $id = DB::table('accounting_accounts')->where('gl_code', $gl)->value('id');
    $old = DB::table('accounting_accounts_transactions as t')->where('accounting_account_id', $id)
        ->selectRaw(
            "SUM(CASE WHEN type='debit' AND {$openingSqlOld} THEN amount ELSE 0 END) dro,
             SUM(CASE WHEN type='credit' AND {$openingSqlOld} THEN amount ELSE 0 END) cro,
             SUM(CASE WHEN type='debit' AND {$periodSqlOld} THEN amount ELSE 0 END) drp,
             SUM(CASE WHEN type='credit' AND {$periodSqlOld} THEN amount ELSE 0 END) crp",
            [$start, $start, $start, $end, $start, $end]
        )->first();
    $new = DB::table('accounting_accounts_transactions as t')->where('accounting_account_id', $id)
        ->selectRaw(
            "SUM(CASE WHEN type='debit' AND {$openingSqlNew} THEN amount ELSE 0 END) dro,
             SUM(CASE WHEN type='credit' AND {$openingSqlNew} THEN amount ELSE 0 END) cro,
             SUM(CASE WHEN type='debit' AND {$periodSqlNew} THEN amount ELSE 0 END) drp,
             SUM(CASE WHEN type='credit' AND {$periodSqlNew} THEN amount ELSE 0 END) crp",
            [$start, $start, $start, $start, $start, $end, $start, $start, $end, $start]
        )->first();
    echo "GL {$gl} OLD open D/C={$old->dro}/{$old->cro} period D/C={$old->drp}/{$old->crp}\n";
    echo "GL {$gl} NEW open D/C={$new->dro}/{$new->cro} period D/C={$new->drp}/{$new->crp}\n";
}

// If opening dated 2025-01-01 instead of 2024-12-31
echo "\n######## If opening moved to 2025-01-01 (server scenario) ########\n";
foreach (['31', '221', '222'] as $gl) {
    $id = DB::table('accounting_accounts')->where('gl_code', $gl)->value('id');
    $sim = DB::table('accounting_accounts_transactions as t')->where('accounting_account_id', $id)
        ->selectRaw(
            "SUM(CASE WHEN type='debit' AND DATE(operation_date) < ? THEN amount ELSE 0 END) dro,
             SUM(CASE WHEN type='credit' AND DATE(operation_date) < ? THEN amount ELSE 0 END) cro,
             SUM(CASE WHEN type='debit' AND DATE(operation_date) >= ? AND DATE(operation_date) <= ? THEN amount ELSE 0 END) drp,
             SUM(CASE WHEN type='credit' AND DATE(operation_date) >= ? AND DATE(operation_date) <= ? THEN amount ELSE 0 END) crp",
            [$start, $start, $start, $end, $start, $end]
        )->first();
    echo "GL {$gl} (strict < start for opening): open D/C={$sim->dro}/{$sim->cro} period D/C={$sim->drp}/{$sim->crp}\n";
}

// refs 7329 and 8133 full entry
echo "\n######## Year-end accrual journals ########\n";
foreach (['7329', '8133'] as $ref) {
    $lines = DB::table('accounting_accounts_transactions as t')
        ->join('accounting_acc_trans_mappings as m', 'm.id', '=', 't.acc_trans_mapping_id')
        ->join('accounting_accounts as a', 'a.id', '=', 't.accounting_account_id')
        ->where('m.ref_no', $ref)
        ->get(['a.gl_code', 't.type', 't.amount', 'm.note']);
    echo "REF {$ref}:\n";
    foreach ($lines as $l) {
        echo "  GL {$l->gl_code} {$l->type} {$l->amount}\n";
    }
}
