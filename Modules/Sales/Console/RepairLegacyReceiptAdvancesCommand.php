<?php

namespace Modules\Sales\Console;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Modules\Sales\Services\LegacyReceiptAdvanceRepairService;

class RepairLegacyReceiptAdvancesCommand extends Command
{
    protected $signature = 'sales:repair-legacy-receipt-advances
        {--tenant=* : Tenant id(s). Omit to run for all tenants}
        {--cash-account= : Fallback cash/bank accounting_accounts.id when it cannot be inferred}
        {--execute : Apply changes (without this flag the command is dry-run only)}';

    protected $description = 'Repair old unbalanced «رصيد متبقي» journals into receipt/payment vouchers visible on receipts lists';

    public function handle(LegacyReceiptAdvanceRepairService $repairService): int
    {
        $execute = (bool) $this->option('execute');
        $cashAccount = $this->option('cash-account') !== null && $this->option('cash-account') !== ''
            ? (int) $this->option('cash-account')
            : null;

        $this->warn('Mode: '.($execute ? 'EXECUTE' : 'DRY-RUN'));
        if (! $execute) {
            $this->comment('Preview only. Re-run with --execute to apply.');
        } elseif (! $this->option('no-interaction')) {
            if (! $this->confirm('This will create payment rows and balance missing cash lines for legacy advances. Continue?', true)) {
                $this->info('Cancelled.');

                return self::SUCCESS;
            }
        }

        $tenantIds = $this->option('tenant');
        $tenants = empty($tenantIds)
            ? Tenant::query()->orderBy('id')->get()
            : Tenant::query()->whereIn('id', $tenantIds)->orderBy('id')->get();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');

            return self::FAILURE;
        }

        $fixed = 0;
        $skipped = 0;

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);
            $this->newLine();
            $this->info('Tenant: '.$tenant->id);

            try {
                $results = $execute
                    ? $repairService->execute($cashAccount)
                    : $repairService->preview($cashAccount);

                if ($results === []) {
                    $this->line('  No legacy advance mappings found.');
                    continue;
                }

                foreach ($results as $row) {
                    if ($row['skipped']) {
                        $skipped++;
                        $this->line("  mapping #{$row['mapping_id']}: SKIP — {$row['skipped']}");
                        continue;
                    }

                    $fixed++;
                    $who = $row['contact'] ?? '?';
                    $amt = number_format((float) ($row['amount'] ?? 0), 2);
                    $this->line("  mapping #{$row['mapping_id']}: {$who} — {$amt}");
                    foreach ($row['changes'] as $change) {
                        $this->line('    - '.$change);
                    }
                }
            } catch (\Throwable $e) {
                $this->error('  Failed: '.$e->getMessage());
            } finally {
                tenancy()->end();
            }
        }

        $this->newLine();
        $this->info("Done. Repairable/fixed: {$fixed}, skipped: {$skipped}".($execute ? '' : ' (dry-run)'));

        return self::SUCCESS;
    }
}
