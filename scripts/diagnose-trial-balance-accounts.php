<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenantId = $argv[1] ?? null;

$tenants = Tenant::with('domains')->get();
echo "Tenants:\n";
foreach ($tenants as $t) {
    $domains = $t->domains->pluck('domain')->implode(', ');
    echo "  {$t->id} | {$domains}\n";
}

if (! $tenantId) {
    if ($tenants->count() === 1) {
        $tenantId = $tenants->first()->id;
        echo "\nUsing sole tenant: {$tenantId}\n";
    } else {
        echo "\nPass tenant id as first argument.\n";
        exit(0);
    }
}

$tenant = Tenant::find($tenantId);
if (! $tenant) {
    fwrite(STDERR, "Tenant not found: {$tenantId}\n");
    exit(1);
}

tenancy()->initialize($tenant);

$start = '2025-01-01';
$end = '2025-12-31';
$glCodes = ['31', '221', '222'];

$accounts = DB::table('accounting_accounts')
    ->whereIn('gl_code', $glCodes)
    ->get(['id', 'gl_code', 'name_ar', 'name_en']);

echo "\nDB: ".DB::connection()->getDatabaseName()."\n";
echo "Accounts:\n";
foreach ($accounts as $a) {
    echo "  {$a->gl_code} id={$a->id} ".($a->name_ar ?: $a->name_en)."\n";
}

$openingSql = "(DATE(t.operation_date) < ? OR (DATE(t.operation_date) = ? AND t.sub_type = 'opening_balance'))";
$periodSql = "(DATE(t.operation_date) >= ? AND DATE(t.operation_date) <= ? AND NOT (DATE(t.operation_date) = ? AND t.sub_type = 'opening_balance'))";

foreach ($accounts as $a) {
    echo "\n=== GL {$a->gl_code} (".($a->name_ar ?: $a->name_en).") ===\n";

    $summary = DB::table('accounting_accounts_transactions as t')
        ->where('t.accounting_account_id', $a->id)
        ->selectRaw(
            "SUM(CASE WHEN t.type='debit' AND {$openingSql} THEN t.amount ELSE 0 END) as debit_open,
             SUM(CASE WHEN t.type='credit' AND {$openingSql} THEN t.amount ELSE 0 END) as credit_open,
             SUM(CASE WHEN t.type='debit' AND {$periodSql} THEN t.amount ELSE 0 END) as debit_period,
             SUM(CASE WHEN t.type='credit' AND {$periodSql} THEN t.amount ELSE 0 END) as credit_period",
            array_merge(
                [$start, $start, $start, $start],
                [$start, $end, $start, $start, $end, $start]
            )
        )
        ->first();

    print_r((array) $summary);

    $lines = DB::table('accounting_accounts_transactions as t')
        ->leftJoin('accounting_acc_trans_mappings as m', 'm.id', '=', 't.acc_trans_mapping_id')
        ->where('t.accounting_account_id', $a->id)
        ->whereRaw($periodSql, [$start, $end, $start])
        ->orderBy('t.operation_date')
        ->orderBy('t.id')
        ->get([
            't.id',
            't.operation_date',
            't.type',
            't.amount',
            't.sub_type',
            DB::raw('m.ref_no as ref_no'),
            DB::raw('m.note as note'),
        ]);

    echo "Period lines: ".count($lines)."\n";
    foreach ($lines as $line) {
        echo sprintf(
            "  %s | %s %s | ref=%s | sub=%s | %s\n",
            $line->operation_date,
            $line->type,
            $line->amount,
            $line->ref_no ?? '-',
            $line->sub_type ?? '-',
            mb_substr((string) ($line->note ?? ''), 0, 80)
        );
    }
}

echo "\n=== Suspect amount lines on GL 31, 221, 222 ===\n";
$suspectAmounts = [214377, 164377, 164377.49, 50000, 276606, 112228.51, 314377, 100000];
$suspect = DB::table('accounting_accounts_transactions as t')
    ->join('accounting_accounts as a', 'a.id', '=', 't.accounting_account_id')
    ->leftJoin('accounting_acc_trans_mappings as m', 'm.id', '=', 't.acc_trans_mapping_id')
    ->whereIn('a.gl_code', $glCodes)
    ->whereIn('t.amount', $suspectAmounts)
    ->orderBy('t.operation_date')
    ->get([
        'a.gl_code',
        'a.name_ar',
        't.operation_date',
        't.type',
        't.amount',
        't.sub_type',
        DB::raw('m.ref_no as ref_no'),
        DB::raw('m.note as note'),
    ]);

foreach ($suspect as $s) {
    echo sprintf(
        "%s | GL %s | %s %s | ref=%s | %s\n",
        $s->operation_date,
        $s->gl_code,
        $s->type,
        $s->amount,
        $s->ref_no ?? '-',
        mb_substr((string) ($s->note ?? ''), 0, 60)
    );
}

// Find journal entries where both 31 and 222 appear
echo "\n=== Shared journal refs between capital and zakat accounts ===\n";
$ids = $accounts->pluck('id')->all();
$shared = DB::table('accounting_accounts_transactions as t')
    ->join('accounting_acc_trans_mappings as m', 'm.id', '=', 't.acc_trans_mapping_id')
    ->join('accounting_accounts as a', 'a.id', '=', 't.accounting_account_id')
    ->whereIn('t.accounting_account_id', $ids)
    ->whereBetween(DB::raw('DATE(t.operation_date)'), [$start, $end])
    ->select('m.ref_no', DB::raw('GROUP_CONCAT(DISTINCT a.gl_code ORDER BY a.gl_code) as gls'), DB::raw('COUNT(*) as lines'))
    ->groupBy('m.ref_no')
    ->havingRaw('COUNT(DISTINCT t.accounting_account_id) > 1')
    ->get();

foreach ($shared as $row) {
    echo "ref={$row->ref_no} gls={$row->gls} lines={$row->lines}\n";
}
