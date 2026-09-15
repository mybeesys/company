<?php

namespace Modules\Accounting\Services\ChartOfAccounts;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountTypes;
use Modules\Accounting\Support\DefaultAccountRoutingMap;
use Modules\Accounting\Support\MyBeeMasterCoaCatalog;
use Modules\Accounting\Support\MyBeeMasterCoaRules;
use Modules\Accounting\Utils\AccountingUtil;
use RuntimeException;

final class MyBeeMasterCoaInstaller
{
    public function install(): void
    {
        if (AccountingAccount::query()->exists()) {
            throw new RuntimeException('Chart of accounts already exists for this company.');
        }

        $hasPostingColumn = Schema::hasColumn('accounting_accounts', 'allow_direct_posting');
        $hasLevelColumn = Schema::hasColumn('accounting_accounts', 'coa_level');

        DB::transaction(function () use ($hasPostingColumn, $hasLevelColumn) {
            $this->installMasterPack($hasPostingColumn, $hasLevelColumn);
        });

        DefaultAccountRoutingMap::seed(true);
    }

    /**
     * Previous compact chart — kept in AccountingUtil::Default_Accounts() / default_accounting_account_types().
     * Not exposed in the UI. Call this only from maintenance/code if a copy of the old pack is needed.
     */
    public function installLegacyPack(): void
    {
        if (AccountingAccount::query()->exists()) {
            throw new RuntimeException('Chart of accounts already exists for this company.');
        }

        if (AccountingAccountTypes::query()->count() === 0) {
            AccountingAccountTypes::query()->insert(AccountingUtil::default_accounting_account_types());
        }

        AccountingAccount::query()->insert(AccountingUtil::Default_Accounts());
        DefaultAccountRoutingMap::seed(true);
    }

    protected function installMasterPack(bool $hasPostingColumn, bool $hasLevelColumn): void
    {
        $now = Carbon::now();
        $userId = Auth::id();
        $categories = MyBeeMasterCoaCatalog::get()['account_categories'];

        $this->replaceUnusedAccountTypesWithMaster($now, $userId);

        $typesByGl = AccountingAccountTypes::query()
            ->where('account_type', 'sub_type')
            ->get()
            ->keyBy('gl_code');

        $accountIdsByGl = [];
        $accounts = collect(MyBeeMasterCoaCatalog::accounts())->sortBy([
            ['level', 'asc'],
            ['gl_code', 'asc'],
        ]);

        foreach ($accounts as $row) {
            $parentGl = $row['parent_gl'];
            $subType = $typesByGl->get($parentGl);
            $parentId = null;
            $primary = $row['account_primary_type'];
            $accountType = MyBeeMasterCoaRules::subtypeAccountType($primary, (string) $parentGl);

            if ($subType) {
                $subTypeId = $subType->id;
                $accountType = MyBeeMasterCoaRules::subtypeAccountType($primary, (string) $subType->gl_code);
            } else {
                $parentId = $accountIdsByGl[$parentGl] ?? null;
                if (! $parentId) {
                    throw new RuntimeException('Missing parent GL '.$parentGl.' for account '.$row['gl_code']);
                }
                $parent = AccountingAccount::query()->find($parentId);
                $subTypeId = $parent?->account_sub_type_id;
                $accountType = $parent?->account_type ?? $accountType;
                $primary = $parent?->account_primary_type ?? $primary;
            }

            $payload = [
                'name_ar' => $row['name_ar'],
                'name_en' => $row['name_en'],
                'gl_code' => $row['gl_code'],
                'account_primary_type' => $primary,
                'account_type' => $accountType,
                'account_sub_type_id' => $subTypeId,
                'detail_type_id' => null,
                'parent_account_id' => $parentId,
                'account_category' => $categories[$row['gl_code']] ?? null,
                'status' => 'active',
                'created_by' => $userId,
            ];

            if ($hasPostingColumn) {
                $payload['allow_direct_posting'] = (bool) $row['allow_direct_posting'];
            }
            if ($hasLevelColumn) {
                $payload['coa_level'] = (int) $row['level'];
            }

            $account = AccountingAccount::query()->create($payload);
            $accountIdsByGl[$row['gl_code']] = $account->id;
        }
    }

    /**
     * Add master-catalog accounts that are missing from an existing tree (non-destructive).
     * Only inserts rows whose parent GL already exists (or is a known subtype).
     *
     * @return array{inserted: int, skipped: int, codes: list<string>}
     */
    public function syncMissingMasterAccounts(): array
    {
        $hasPostingColumn = Schema::hasColumn('accounting_accounts', 'allow_direct_posting');
        $hasLevelColumn = Schema::hasColumn('accounting_accounts', 'coa_level');
        $categories = MyBeeMasterCoaCatalog::get()['account_categories'];
        $existing = AccountingAccount::query()->pluck('id', 'gl_code')->all();
        $typesByGl = AccountingAccountTypes::query()
            ->where('account_type', 'sub_type')
            ->get()
            ->keyBy('gl_code');

        $inserted = 0;
        $skipped = 0;
        $codes = [];

        $accounts = collect(MyBeeMasterCoaCatalog::accounts())->sortBy([
            ['level', 'asc'],
            ['gl_code', 'asc'],
        ]);

        DB::transaction(function () use (
            $accounts,
            $categories,
            $hasPostingColumn,
            $hasLevelColumn,
            &$existing,
            $typesByGl,
            &$inserted,
            &$skipped,
            &$codes
        ) {
            foreach ($accounts as $row) {
                $gl = (string) $row['gl_code'];
                if (isset($existing[$gl])) {
                    continue;
                }

                $parentGl = $row['parent_gl'];
                $subType = $typesByGl->get($parentGl);
                $parentId = null;
                $primary = $row['account_primary_type'];
                $accountType = MyBeeMasterCoaRules::subtypeAccountType($primary, (string) $parentGl);
                $subTypeId = null;

                if ($subType) {
                    $subTypeId = $subType->id;
                    $accountType = MyBeeMasterCoaRules::subtypeAccountType($primary, (string) $subType->gl_code);
                } else {
                    $parentId = $existing[$parentGl] ?? null;
                    if (! $parentId) {
                        $skipped++;
                        continue;
                    }
                    $parent = AccountingAccount::query()->find($parentId);
                    $subTypeId = $parent?->account_sub_type_id;
                    $accountType = $parent?->account_type ?? $accountType;
                    $primary = $parent?->account_primary_type ?? $primary;
                }

                $payload = [
                    'name_ar' => $row['name_ar'],
                    'name_en' => $row['name_en'],
                    'gl_code' => $gl,
                    'account_primary_type' => $primary,
                    'account_type' => $accountType,
                    'account_sub_type_id' => $subTypeId,
                    'detail_type_id' => null,
                    'parent_account_id' => $parentId,
                    'account_category' => $categories[$gl] ?? null,
                    'status' => 'active',
                    'created_by' => Auth::id(),
                ];

                if ($hasPostingColumn) {
                    $payload['allow_direct_posting'] = (bool) $row['allow_direct_posting'];
                }
                if ($hasLevelColumn) {
                    $payload['coa_level'] = (int) $row['level'];
                }

                $account = AccountingAccount::query()->create($payload);
                $existing[$gl] = $account->id;
                $codes[] = $gl;
                $inserted++;
            }
        });

        DefaultAccountRoutingMap::ensureMissingRoutes();

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
            'codes' => $codes,
        ];
    }

    /**
     * Empty trees often already have leftover sub-types from an earlier default-account click.
     * With no accounts yet, those rows are unused and can be replaced by the My Bee master set.
     */
    protected function replaceUnusedAccountTypesWithMaster(Carbon $now, mixed $userId): void
    {
        AccountingAccountTypes::query()->delete();

        $typeRows = [];
        foreach (MyBeeMasterCoaCatalog::types() as $type) {
            $typeRows[] = [
                'name_ar' => $type['name_ar'],
                'name_en' => $type['name_en'],
                'gl_code' => $type['gl_code'],
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => $type['account_primary_type'],
                'parent_id' => null,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        AccountingAccountTypes::query()->insert($typeRows);
    }
}
