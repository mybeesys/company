<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Accounting\Services\ChartOfAccounts\MyBeeMasterCoaInstaller;
use Modules\Accounting\Support\DefaultAccountRoutingMap;

/**
 * Sync workbook example leaves (Customer 1, Bank Account 1, Supplier 1, …)
 * that were previously omitted from the compiled master catalog.
 * Non-destructive: inserts missing GLs only.
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
