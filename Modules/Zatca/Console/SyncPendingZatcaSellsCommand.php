<?php

namespace Modules\Zatca\Console;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Modules\Zatca\Services\ZatcaAutoSyncService;

class SyncPendingZatcaSellsCommand extends Command
{
    protected $signature = 'zatca:sync-pending-sells';

    protected $description = 'Daily ZATCA sync for tenants with auto_sync_mode=daily';

    public function handle(ZatcaAutoSyncService $autoSync): int
    {
        $tenants = Tenant::all();
        $this->info('ZATCA daily sync starting for '.$tenants->count().' tenant(s).');

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            try {
                $result = $autoSync->syncPendingForDaily();
                $this->line(sprintf(
                    '[tenant %s] success=%d failed=%d',
                    $tenant->id,
                    $result['success'],
                    $result['failed']
                ));
            } catch (\Throwable $e) {
                $this->error('[tenant '.$tenant->id.'] '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
