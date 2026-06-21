<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Modules\Accounting\Support\ImportedAccountTypeSync;

$updated = ImportedAccountTypeSync::syncFromPrimaryType();
echo "Synced account_type for {$updated} income/expense accounts.\n";
