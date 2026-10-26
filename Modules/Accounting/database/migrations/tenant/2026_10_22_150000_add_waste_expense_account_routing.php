<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\AccountsRoting;
use Modules\Accounting\Support\DefaultAccountRoutingMap;

return new class extends Migration
{
    public function up(): void
    {
        $table = (new AccountsRoting)->getTable();
        if (! Schema::hasTable($table)) {
            return;
        }

        DefaultAccountRoutingMap::ensureMissingRoutes();
    }

    public function down(): void
    {
        // Keep configured waste_expense routing.
    }
};
