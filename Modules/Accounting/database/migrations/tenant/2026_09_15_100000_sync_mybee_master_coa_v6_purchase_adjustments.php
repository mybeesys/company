<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Accounting\Services\ChartOfAccounts\MyBeeMasterCoaInstaller;
use Modules\Accounting\Support\DefaultAccountRoutingMap;

/**
 * Sync missing My Bee master COA v6 accounts (e.g. 514/51401/51402) and fill routing gaps.
 * Non-destructive: never deletes or overwrites existing accounts / configured routes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('accounting_accounts')) {
            return;
        }

        if (\Modules\Accounting\Models\AccountingAccount::query()->exists()) {
            app(MyBeeMasterCoaInstaller::class)->syncMissingMasterAccounts();
        } else {
            DefaultAccountRoutingMap::ensureMissingRoutes();
        }
    }

    public function down(): void
    {
        // Non-destructive sync — no automatic rollback of inserted accounts.
    }
};
