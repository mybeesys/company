<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenantId = $argv[1] ?? 'test1';
$tenant = Tenant::find($tenantId);
if (! $tenant) {
    fwrite(STDERR, "Tenant not found: {$tenantId}\n");
    exit(1);
}

tenancy()->initialize($tenant);
echo 'DB: '.DB::connection()->getDatabaseName()."\n\n";

echo "=== GL accounts 31, 221, 222 ===\n";
$accounts = DB::table('accounting_accounts')
    ->whereIn('gl_code', ['31', '221', '222'])
    ->get(['id', 'gl_code', 'name_ar']);
foreach ($accounts as $a) {
    echo "id={$a->id} gl={$a->gl_code} {$a->name_ar}\n";
}
if ($accounts->isEmpty()) {
    echo "WARNING: none of these GL codes exist!\n";
}

echo "\n=== Opening balance mappings ===\n";
$openings = DB::table('accounting_acc_trans_mappings as m')
    ->where(function ($q) {
        $q->where('m.ref_no', 'like', 'OPENING%')
            ->orWhere('m.note', 'like', '%افتتاح%');
    })
    ->orderByDesc('m.id')
    ->get(['m.id', 'm.ref_no', 'm.operation_date', 'm.note', 'm.created_at']);

if ($openings->isEmpty()) {
    echo "NONE — no opening journal was imported.\n";
} else {
    foreach ($openings as $o) {
        $lines = DB::table('accounting_accounts_transactions')
            ->where('acc_trans_mapping_id', $o->id)
            ->count();
        echo "id={$o->id} ref={$o->ref_no} date={$o->operation_date} lines={$lines} note={$o->note}\n";
    }
}

echo "\n=== opening_balance sub_type lines (any account) ===\n";
$obLines = DB::table('accounting_accounts_transactions as t')
    ->join('accounting_accounts as a', 'a.id', '=', 't.accounting_account_id')
    ->leftJoin('accounting_acc_trans_mappings as m', 'm.id', '=', 't.acc_trans_mapping_id')
    ->where('t.sub_type', 'opening_balance')
    ->select(['a.gl_code', 't.type', 't.amount', 't.operation_date', 'm.ref_no'])
    ->orderBy('a.gl_code')
    ->limit(20)
    ->get();

if ($obLines->isEmpty()) {
    echo "NONE\n";
} else {
    foreach ($obLines as $l) {
        echo "GL {$l->gl_code} {$l->type} {$l->amount} | {$l->operation_date} | {$l->ref_no}\n";
    }
    $total = DB::table('accounting_accounts_transactions')->where('sub_type', 'opening_balance')->count();
    if ($total > 20) {
        echo "... and ".($total - 20)." more lines\n";
    }
}

echo "\n=== Was old entry 3302 deleted? ===\n";
$old = DB::table('accounting_acc_trans_mappings')->where('id', 3302)->first();
echo $old ? "STILL EXISTS ref={$old->ref_no}\n" : "Deleted (id 3302 not found).\n";
