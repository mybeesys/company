<?php

namespace Modules\Zatca\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Zatca\Services\ZatcaAutoSyncService;

/**
 * Runs after the HTTP response so invoice create is not blocked.
 * Does not require a queue worker (dispatched via afterResponse).
 */
class SyncSellInvoiceToZatcaJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $transactionId,
        public ?string $tenantId = null
    ) {}

    public function handle(ZatcaAutoSyncService $autoSync): void
    {
        if ($this->tenantId) {
            $tenant = Tenant::query()->find($this->tenantId);
            if ($tenant) {
                tenancy()->initialize($tenant);
            }
        }

        $autoSync->syncTransaction($this->transactionId);
    }
}
