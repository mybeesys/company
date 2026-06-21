<?php

namespace Modules\Accounting\Support;

use Modules\Accounting\Models\AccountingAccount;

final class ImportedAccountTypeSync
{
    /**
     * Imported charts set account_primary_type only; reports expect account_type for income/expense lines.
     */
    public static function syncFromPrimaryType(): int
    {
        $updated = 0;

        AccountingAccount::query()
            ->whereIn('account_primary_type', ['income', 'expenses'])
            ->where(function ($query) {
                $query->whereNull('account_type')
                    ->orWhere('account_type', 'normal')
                    ->orWhere('account_type', '');
            })
            ->orderBy('id')
            ->chunkById(200, function ($accounts) use (&$updated) {
                foreach ($accounts as $account) {
                    $primary = (string) $account->account_primary_type;
                    if ($primary === '') {
                        continue;
                    }

                    $account->account_type = $primary;
                    $account->save();
                    $updated++;
                }
            });

        return $updated;
    }
}
