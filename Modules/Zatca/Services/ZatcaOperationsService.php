<?php

namespace Modules\Zatca\Services;

use Illuminate\Support\Facades\DB;
use Modules\Zatca\Models\ZatcaInvoiceSync;
use Modules\Zatca\Models\ZatcaSetting;
use RuntimeException;

class ZatcaOperationsService
{
    public const SANDBOX_ENVIRONMENTS = ['local', 'simulation'];

    /**
     * @return array{local: int, simulation: int, total: int}
     */
    public function sandboxCounts(): array
    {
        $local = ZatcaInvoiceSync::query()
            ->where('synced_environment', 'local')
            ->where('status', ZatcaInvoiceSync::STATUS_SYNCED)
            ->count();

        $simulation = ZatcaInvoiceSync::query()
            ->where('synced_environment', 'simulation')
            ->where('status', ZatcaInvoiceSync::STATUS_SYNCED)
            ->count();

        return [
            'local' => $local,
            'simulation' => $simulation,
            'total' => $local + $simulation,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateOperations(ZatcaSetting $setting, array $data): ZatcaSetting
    {
        $setting->fill([
            'auto_sync_mode' => $data['auto_sync_mode'] ?? 'disable',
            'disable_discount' => (bool) ($data['disable_discount'] ?? false),
            'disable_order_tax' => (bool) ($data['disable_order_tax'] ?? false),
            'default_sales_discount' => (float) ($data['default_sales_discount'] ?? 0),
            'lock_synced_invoices' => (bool) ($data['lock_synced_invoices'] ?? true),
        ]);
        $setting->save();

        return $setting->fresh();
    }

    /**
     * Purge sandbox sync rows only (never ERP transactions).
     *
     * @return array{deleted: int}
     */
    public function purgeSandboxSyncs(ZatcaSetting $setting): array
    {
        $env = (string) $setting->zatca_environment;
        if (! in_array($env, self::SANDBOX_ENVIRONMENTS, true)) {
            throw new RuntimeException(__('zatca::lang.ops_purge_production_blocked'));
        }

        return DB::transaction(function () use ($setting) {
            $deleted = ZatcaInvoiceSync::query()
                ->whereIn('synced_environment', self::SANDBOX_ENVIRONMENTS)
                ->delete();

            // Also clear legacy rows without environment tag while on sandbox.
            $deleted += ZatcaInvoiceSync::query()
                ->whereNull('synced_environment')
                ->delete();

            $setting->last_invoice_hash = null;
            $setting->invoice_counter = 0;
            $setting->save();

            return ['deleted' => $deleted];
        });
    }

    public function isAutoSyncEnabled(ZatcaSetting $setting): bool
    {
        return in_array((string) $setting->auto_sync_mode, ['instant', 'daily'], true)
            && $setting->isConfigured();
    }
}
