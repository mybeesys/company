<?php

namespace Modules\Zatca\Services;

use Illuminate\Support\Facades\Log;
use Modules\General\Models\Transaction;
use Modules\Zatca\Jobs\SyncSellInvoiceToZatcaJob;
use Modules\Zatca\Models\ZatcaInvoiceSync;
use Modules\Zatca\Models\ZatcaSetting;
use Throwable;

class ZatcaAutoSyncService
{
    public function __construct(
        private readonly ZatcaSellSyncService $sellSync
    ) {}

    /**
     * Queue/run instant sync after a sell invoice or credit note is created.
     */
    public function queueIfInstant(int $transactionId): void
    {
        try {
            $setting = ZatcaSetting::current();
            if ((string) $setting->auto_sync_mode !== 'instant' || ! $setting->isConfigured()) {
                return;
            }

            SyncSellInvoiceToZatcaJob::dispatch(
                $transactionId,
                tenant('id') ? (string) tenant('id') : null
            )->afterResponse();
        } catch (Throwable $e) {
            Log::warning('ZATCA instant sync dispatch failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync a single sell / sell-return (used by job / daily command).
     * Never throws to the HTTP create path.
     */
    public function syncTransaction(int $transactionId): void
    {
        try {
            $setting = ZatcaSetting::current();
            if (! $setting->isConfigured()) {
                return;
            }

            $transaction = Transaction::query()
                ->whereIn('type', ['sell', 'sell-return'])
                ->whereIn('status', ['approved', 'final'])
                ->with(['client.billingAddress', 'sell_lines.product', 'purchases_lines.product'])
                ->find($transactionId);

            if (! $transaction) {
                return;
            }

            $reportType = $this->resolveReportType($transaction);
            $this->sellSync->syncOne($transaction, $reportType, $setting);
        } catch (Throwable $e) {
            Log::warning('ZATCA auto sync failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync pending/failed sells and credit notes for daily mode.
     *
     * @return array{success: int, failed: int}
     */
    public function syncPendingForDaily(): array
    {
        $setting = ZatcaSetting::current();
        if ((string) $setting->auto_sync_mode !== 'daily' || ! $setting->isConfigured()) {
            return ['success' => 0, 'failed' => 0];
        }

        $success = 0;
        $failed = 0;

        // Tax invoices first so credit notes can reference synced parents.
        foreach (['sell', 'sell-return'] as $type) {
            $transactions = Transaction::query()
                ->where('type', $type)
                ->whereIn('status', ['approved', 'final'])
                ->with(['client.billingAddress', 'sell_lines.product', 'purchases_lines.product'])
                ->whereDoesntHave('zatcaInvoiceSync', function ($q) {
                    $q->where('status', ZatcaInvoiceSync::STATUS_SYNCED);
                })
                ->orderBy('id')
                ->limit(200)
                ->get();

            foreach ($transactions as $transaction) {
                $result = $this->sellSync->syncOne(
                    $transaction,
                    $this->resolveReportType($transaction),
                    $setting->fresh()
                );
                if ($result['success']) {
                    $success++;
                } else {
                    $failed++;
                }
            }
        }

        return compact('success', 'failed');
    }

    private function resolveReportType(Transaction $transaction): string
    {
        if ((string) $transaction->type === 'sell-return' && $transaction->parent_id) {
            $parentSync = ZatcaInvoiceSync::query()
                ->where('transaction_id', $transaction->parent_id)
                ->where('status', ZatcaInvoiceSync::STATUS_SYNCED)
                ->first();
            $parentType = strtoupper((string) ($parentSync?->report_type ?? ''));
            if (in_array($parentType, ['B2C', 'B2B'], true)) {
                return $parentType;
            }
        }

        $contact = $transaction->client;
        if (! $contact && $transaction->parent_id) {
            $contact = Transaction::query()->with('client')->find($transaction->parent_id)?->client;
        }

        $tax = preg_replace('/\D+/', '', (string) ($contact?->tax_number ?? '')) ?: '';

        if ($tax !== '' && preg_match('/^3\d{13}3$/', $tax)) {
            return 'B2B';
        }

        return 'B2C';
    }
}
