<?php

namespace Modules\Sales\Console;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Modules\General\Models\Transaction;
use Modules\Sales\Services\SellReturnRepairService;

class RepairSellReturnsCommand extends Command
{
    protected $signature = 'sales:repair-sell-returns
        {--tenant=* : Tenant id(s). Omit to run for all tenants}
        {--id= : Repair one sell-return by transaction id}
        {--ref= : Repair one sell-return by ref_no}
        {--parent= : Repair all sell-returns linked to this sell invoice id}
        {--execute : Apply changes (without this flag the command is dry-run only)}';

    protected $description = 'Repair existing sell-return invoices: line/invoice discounts, warehouse, customer AR journals, inventory establishment';

    public function handle(SellReturnRepairService $repairService): int
    {
        $execute = (bool) $this->option('execute');
        $mode = $execute ? 'EXECUTE' : 'DRY-RUN';
        $this->warn("Mode: {$mode}");

        if (! $execute) {
            $this->comment('Preview only. Re-run with --execute to apply.');
        } elseif (! $this->option('no-interaction')) {
            if (! $this->confirm('This will rewrite sell-return amounts and auto journals. Continue?', true)) {
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

        $totalFixed = 0;
        $totalSkipped = 0;

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);
            $this->newLine();
            $this->info('Tenant: '.$tenant->id);

            $query = Transaction::query()
                ->where('type', 'sell-return')
                ->whereNotNull('parent_id')
                ->orderBy('id');

            if ($this->option('id')) {
                $query->where('id', (int) $this->option('id'));
            }
            if ($this->option('ref')) {
                $query->where('ref_no', (string) $this->option('ref'));
            }
            if ($this->option('parent')) {
                $query->where('parent_id', (int) $this->option('parent'));
            }

            $returns = $query->get();
            if ($returns->isEmpty()) {
                $this->line('  No matching sell-returns.');
                tenancy()->end();

                continue;
            }

            foreach ($returns as $returnTx) {
                $result = $execute
                    ? $repairService->execute($returnTx)
                    : $repairService->preview($returnTx);

                if ($result['skipped']) {
                    $totalSkipped++;
                    $this->line("  #{$result['id']} {$result['ref_no']}: SKIP — {$result['skipped']}");

                    continue;
                }

                $totalFixed++;
                $this->line("  #{$result['id']} {$result['ref_no']}:");
                foreach ($result['changes'] as $change) {
                    $this->line('    - '.$change);
                }
                $this->line(sprintf(
                    '    totals: before=%s disc=%s afterDisc=%s vat=%s final=%s → final=%s',
                    $result['before']['total_before_tax'] ?? '-',
                    $result['before']['discount_amount'] ?? '-',
                    $result['before']['totalAfterDiscount'] ?? '-',
                    $result['before']['tax_amount'] ?? '-',
                    $result['before']['final_total'] ?? '-',
                    $result['after']['final_total'] ?? '-'
                ));
            }

            tenancy()->end();
        }

        $this->newLine();
        $this->info("Done. processed={$totalFixed}, skipped={$totalSkipped}, mode={$mode}");

        if (! $execute) {
            $this->comment('To apply: php artisan sales:repair-sell-returns --tenant=TENANT_ID --execute');
        }

        return self::SUCCESS;
    }
}
