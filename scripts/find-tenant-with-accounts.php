<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$glCodes = ['31', '221', '222', '111041'];

foreach (Tenant::all() as $tenant) {
    tenancy()->initialize($tenant);
    $db = DB::connection()->getDatabaseName();
    $count = DB::table('accounting_accounts')->count();
    $hits = DB::table('accounting_accounts')->whereIn('gl_code', $glCodes)->pluck('gl_code')->all();
    echo "{$tenant->id} | db={$db} | accounts={$count} | hits=".implode(',', $hits)."\n";
}
