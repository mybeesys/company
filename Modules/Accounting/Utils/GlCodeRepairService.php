<?php

namespace Modules\Accounting\Utils;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountTypes;

class GlCodeRepairService
{
    /**
     * @return array{sub_types: int, accounts: int}
     */
    public static function repairAll(): array
    {
        $subTypesUpdated = self::repairSubTypeGlCodes();
        $accountsUpdated = self::repairAccountGlCodes();

        return [
            'sub_types' => $subTypesUpdated,
            'accounts' => $accountsUpdated,
        ];
    }

    public static function repairSubTypeGlCodes(): int
    {
        $primaryTypes = AccountingAccountTypes::accounting_primary_type();
        $updated = 0;

        $subTypes = AccountingAccountTypes::query()
            ->where('account_type', 'sub_type')
            ->orderBy('account_primary_type')
            ->orderBy('gl_code')
            ->orderBy('id')
            ->get()
            ->groupBy('account_primary_type');

        foreach ($subTypes as $primary => $types) {
            if (! isset($primaryTypes[$primary])) {
                continue;
            }

            $primaryGlc = (string) $primaryTypes[$primary]['GLC'];
            $lastCode = null;

            foreach ($types->sortBy('gl_code')->values() as $type) {
                $newCode = AccountingUtil::nextGlCodeFromParentCode($primaryGlc, $lastCode, $primary);
                $lastCode = $newCode;

                if ((string) $type->gl_code !== $newCode) {
                    $type->gl_code = $newCode;
                    $type->save();
                    $updated++;
                }
            }
        }

        return $updated;
    }

    public static function repairAccountGlCodes(): int
    {
        $updates = [];

        $subTypes = AccountingAccountTypes::query()
            ->where('account_type', 'sub_type')
            ->orderBy('id')
            ->get();

        foreach ($subTypes as $subType) {
            $prefix = trim((string) $subType->gl_code);
            if ($prefix === '') {
                continue;
            }

            $primary = $subType->account_primary_type;

            $roots = AccountingAccount::query()
                ->where('account_sub_type_id', $subType->id)
                ->whereNull('parent_account_id')
                ->orderBy('gl_code')
                ->orderBy('id')
                ->get();

            $lastCode = null;
            foreach ($roots as $account) {
                $newCode = AccountingUtil::nextGlCodeFromParentCode($prefix, $lastCode, $primary);
                $lastCode = $newCode;
                self::queueGlCodeUpdate($account, $newCode, $updates);
                self::collectChildGlCodeUpdates((int) $account->id, $newCode, $primary, $updates);
            }
        }

        if ($updates === []) {
            return 0;
        }

        self::applyAccountGlCodeUpdates($updates);

        return count($updates);
    }

    /**
     * @param  array<int, string>  $updates
     */
    protected static function collectChildGlCodeUpdates(
        int $parentId,
        string $parentCode,
        ?string $accountPrimaryType,
        array &$updates
    ): void {
        $children = AccountingAccount::query()
            ->where('parent_account_id', $parentId)
            ->orderBy('gl_code')
            ->orderBy('id')
            ->get();

        $lastCode = null;
        foreach ($children as $child) {
            $newCode = AccountingUtil::nextGlCodeFromParentCode(
                $parentCode,
                $lastCode,
                $accountPrimaryType ?? $child->account_primary_type
            );
            $lastCode = $newCode;
            self::queueGlCodeUpdate($child, $newCode, $updates);
            self::collectChildGlCodeUpdates(
                (int) $child->id,
                $newCode,
                $accountPrimaryType ?? $child->account_primary_type,
                $updates
            );
        }
    }

    /**
     * @param  array<int, string>  $updates
     */
    protected static function queueGlCodeUpdate(AccountingAccount $account, string $newCode, array &$updates): void
    {
        if ((string) $account->gl_code !== $newCode) {
            $updates[$account->id] = $newCode;
        }
    }

    /**
     * @param  array<int, string>  $updates
     */
    protected static function applyAccountGlCodeUpdates(array $updates): void
    {
        foreach (array_keys($updates) as $id) {
            DB::table('accounting_accounts')
                ->where('id', $id)
                ->update(['gl_code' => '_repair_'.$id]);
        }

        foreach ($updates as $id => $code) {
            DB::table('accounting_accounts')
                ->where('id', $id)
                ->update(['gl_code' => $code]);
        }
    }
}
