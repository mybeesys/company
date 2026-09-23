<?php

declare(strict_types=1);

namespace Modules\Accounting\Console;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountTypes;

/**
 * Safely reclassify partners current (331*) and drawings (332*) from equity
 * into current liabilities — updates types / subtype / parent only.
 * Never deletes accounts or journal lines; gl_code stays unchanged.
 */
class ReclassifyPartnersToCurrentLiabilitiesCommand extends Command
{
    protected $signature = 'accounting:reclassify-partners-to-current-liabilities
        {--tenant=* : Tenant id(s). Omit to run for all tenants}
        {--execute : Apply changes (without this flag the command is dry-run only)}';

    protected $description = 'Move partners current / drawings COA accounts under current liabilities without deleting anything';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $this->warn('Mode: '.($execute ? 'EXECUTE' : 'DRY-RUN'));
        if (! $execute) {
            $this->comment('Preview only. Re-run with --execute to apply.');
        }

        $tenantIds = $this->option('tenant');
        $tenants = empty($tenantIds)
            ? Tenant::query()->orderBy('id')->get()
            : Tenant::query()->whereIn('id', $tenantIds)->orderBy('id')->get();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');

            return self::FAILURE;
        }

        $updatedTotal = 0;

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);
            $this->newLine();
            $this->info('Tenant: '.$tenant->id.' | DB: '.DB::connection()->getDatabaseName());

            try {
                $updatedTotal += $this->reclassifyTenant($execute);
            } catch (\Throwable $e) {
                $this->error('  Failed: '.$e->getMessage());
            } finally {
                tenancy()->end();
            }
        }

        $this->newLine();
        $this->info("Done. Accounts touched: {$updatedTotal}".($execute ? '' : ' (dry-run)'));

        return self::SUCCESS;
    }

    private function reclassifyTenant(bool $execute): int
    {
        $targets = AccountingAccount::query()
            ->where(function ($q) {
                $q->where('gl_code', 'like', '331%')
                    ->orWhere('gl_code', 'like', '332%')
                    ->orWhere('name_ar', 'like', '%جاري الشركاء%')
                    ->orWhere('name_ar', 'like', '%مسحوبات الملاك%')
                    ->orWhere('name_ar', 'like', '%مسحوبات الشركاء%')
                    ->orWhere('name_en', 'like', '%Partners Current%')
                    ->orWhere('name_en', 'like', '%Owners Drawings%');
            })
            ->orderBy('gl_code')
            ->get();

        if ($targets->isEmpty()) {
            $this->line('  No partner/drawings accounts found.');

            return 0;
        }

        $template = AccountingAccount::query()
            ->where(function ($q) {
                $q->where('account_type', 'current_liabilities')
                    ->orWhere('gl_code', 'like', '21%');
            })
            ->whereIn('account_primary_type', ['liability', 'liabilities'])
            ->orderByRaw("CASE WHEN gl_code LIKE '212%' THEN 0 WHEN gl_code LIKE '211%' THEN 1 ELSE 2 END")
            ->orderBy('gl_code')
            ->first();

        if (! $template) {
            $this->error('  No current-liability template account found — skipped.');

            return 0;
        }

        $subtypeId = $template->account_sub_type_id;
        if (! $subtypeId) {
            $subtypeId = AccountingAccountTypes::query()
                ->where(function ($q) {
                    $q->where('name_en', 'like', '%Current Liabilities%')
                        ->orWhere('name_ar', 'like', '%خصوم متداولة%')
                        ->orWhere('name_ar', 'like', '%التزامات متداولة%');
                })
                ->value('id');
        }

        $headerParentId = $template->parent_account_id;
        $header = $this->resolveOrCreatePartnersHeader($headerParentId, $subtypeId, $execute);

        $this->table(
            ['id', 'gl', 'name', 'primary→', 'type→', 'parent→'],
            $targets->map(function ($account) use ($template, $subtypeId, $header) {
                $isRoot = $this->isPartnerRoot($account);

                return [
                    $account->id,
                    $account->gl_code,
                    mb_substr((string) ($account->name_ar ?: $account->name_en), 0, 40),
                    ($account->account_primary_type ?? '').' → liabilities',
                    ($account->account_type ?? '').' → current_liabilities',
                    $isRoot && $header
                        ? (($account->parent_account_id ?? 'null').' → '.$header->id.' ('.$header->gl_code.')')
                        : 'unchanged',
                ];
            })->all()
        );

        if (! $execute) {
            return $targets->count();
        }

        $touched = 0;
        DB::transaction(function () use ($targets, $template, $subtypeId, $header, &$touched) {
            foreach ($targets as $account) {
                $payload = [
                    'account_primary_type' => 'liabilities',
                    'account_type' => 'current_liabilities',
                ];

                if ($subtypeId) {
                    $payload['account_sub_type_id'] = $subtypeId;
                }

                if ($this->isPartnerRoot($account) && $header) {
                    $payload['parent_account_id'] = $header->id;
                }

                $account->fill($payload);
                $account->save();
                $touched++;
            }

            // Align newly created header subtype with template when present.
            if ($header && $subtypeId && (int) $header->account_sub_type_id !== (int) $subtypeId) {
                $header->account_sub_type_id = $subtypeId;
                $header->account_primary_type = 'liabilities';
                $header->account_type = 'current_liabilities';
                $header->save();
            }

            unset($template);
        });

        $this->info("  Updated {$touched} account(s).");

        return $touched;
    }

    private function isPartnerRoot(object $account): bool
    {
        $gl = preg_replace('/[^0-9]/', '', (string) ($account->gl_code ?? '')) ?? '';

        return in_array($gl, ['33101', '33201', '331', '332'], true)
            || preg_match('/^(33101|33201)$/', $gl) === 1;
    }

    private function resolveOrCreatePartnersHeader(
        mixed $headerParentId,
        mixed $subtypeId,
        bool $execute
    ): ?AccountingAccount {
        $existing = AccountingAccount::query()
            ->where(function ($q) {
                $q->where('gl_code', '219')
                    ->orWhere('gl_code', '21901')
                    ->orWhere('name_ar', 'like', '%جاري ومسحوبات الشركاء%')
                    ->orWhere('name_en', 'like', '%Partners Current & Drawings%');
            })
            ->orderBy('gl_code')
            ->first();

        if ($existing) {
            return $existing;
        }

        // Prefer attaching under the same parent as other 21x headers (often null / subtype root).
        if (! $execute) {
            $this->comment('  Would create header GL 219 جاري ومسحوبات الشركاء under current liabilities.');

            return new AccountingAccount([
                'id' => 0,
                'gl_code' => '219',
                'name_ar' => 'جاري ومسحوبات الشركاء',
                'parent_account_id' => $headerParentId,
            ]);
        }

        $header = new AccountingAccount;
        $payload = [
            'name_ar' => 'جاري ومسحوبات الشركاء',
            'name_en' => 'Partners Current & Drawings',
            'gl_code' => '219',
            'parent_account_id' => $headerParentId,
            'account_primary_type' => 'liabilities',
            'account_type' => 'current_liabilities',
            'account_sub_type_id' => $subtypeId,
            'status' => 'active',
        ];
        if (Schema::hasColumn('accounting_accounts', 'allow_direct_posting')) {
            $payload['allow_direct_posting'] = false;
        }
        $header->fill($payload);
        $header->save();

        $this->info('  Created header account 219 / جاري ومسحوبات الشركاء (id='.$header->id.').');

        return $header;
    }
}
