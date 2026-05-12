<?php

namespace Modules\Accounting\Utils;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\AccountingAccountTypes;

class AccountingFullResetService
{
    /**
     * Tenant tables owned by the Accounting module (order irrelevant when FK checks are off).
     */
    private const ACCOUNTING_TABLES = [
        'periodic_inventory_items',
        'periodic_inventories',
        'accounting_accounts_transactions',
        'accounting_acc_trans_mappings',
        'accounts_rotings',
        'accounting_accounts',
        'accounting_cost_centers',
        'accounting_account_types',
    ];

    public static function isAllowed(): bool
    {
        if (config('accounting.allow_full_reset', false)) {
            return true;
        }

        return app()->environment(['local', 'staging']);
    }

    public static function ensureAllowedOrAbort(): void
    {
        if (! static::isAllowed()) {
            abort(403, 'Full accounting reset is disabled. Set ACCOUNTING_ALLOW_FULL_RESET=true in .env, or use APP_ENV=local|staging.');
        }
    }

    /**
     * Truncate all accounting module tables for the current tenant connection, then restore
     * default account types only. The chart of accounts (accounting_accounts) stays empty until
     * the user runs "Create default accounts" (same as a fresh tenant).
     *
     * @throws \RuntimeException
     */
    public static function truncateAndReseedDefaults(): void
    {
        if (! Auth::check()) {
            throw new \RuntimeException('Authentication required.');
        }

        Schema::disableForeignKeyConstraints();

        try {
            foreach (self::ACCOUNTING_TABLES as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $now = now();
        $typeRows = collect(AccountingUtil::default_accounting_account_types())
            ->map(static fn (array $row) => array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]))
            ->all();

        AccountingAccountTypes::insert($typeRows);
    }
}
