<?php

namespace Modules\Zatca\Services;

use Modules\General\Models\Transaction;
use Modules\Zatca\Models\ZatcaInvoiceSync;
use Modules\Zatca\Models\ZatcaSetting;
use RuntimeException;
use Throwable;

class ZatcaSellSyncService
{
    /**
     * @return array{sync: ZatcaInvoiceSync, success: bool, message: string}
     */
    public function syncOne(Transaction $transaction, string $reportType, ZatcaSetting $setting): array
    {
        $reportType = strtoupper($reportType);
        if (! in_array($reportType, ['B2C', 'B2B'], true)) {
            throw new RuntimeException(__('zatca::lang.report_type_invalid'));
        }

        if (! $setting->isConfigured()) {
            throw new RuntimeException(__('zatca::lang.send_requires_credentials'));
        }

        $sync = ZatcaInvoiceSync::forTransaction((int) $transaction->id);
        $sync->report_type = $reportType;
        $sync->last_attempt_at = now();
        $sync->save();

        try {
            // Real B2C/B2B reporting will replace this placeholder.
            throw new RuntimeException(__('zatca::lang.send_not_implemented', [
                'ref' => $transaction->ref_no,
                'type' => $reportType,
            ]));
        } catch (Throwable $e) {
            $sync->status = ZatcaInvoiceSync::STATUS_FAILED;
            $sync->last_error = $e->getMessage();
            $sync->synced_at = null;
            $sync->save();

            return [
                'sync' => $sync->fresh(),
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param  array<int, array{id:int, report_type:string}>  $items
     * @return array{success:int, failed:int, messages:array<int, string>}
     */
    public function syncMany(array $items, ZatcaSetting $setting): array
    {
        $success = 0;
        $failed = 0;
        $messages = [];

        foreach ($items as $item) {
            $transaction = Transaction::query()
                ->where('type', 'sell')
                ->whereIn('status', ['approved', 'final'])
                ->find($item['id'] ?? null);

            if (! $transaction) {
                $failed++;
                $messages[] = __('zatca::lang.sell_invoice_missing', ['id' => $item['id'] ?? '?']);

                continue;
            }

            $result = $this->syncOne($transaction, (string) ($item['report_type'] ?? 'B2C'), $setting);
            if ($result['success']) {
                $success++;
            } else {
                $failed++;
            }
            $messages[] = $transaction->ref_no.': '.$result['message'];
        }

        return compact('success', 'failed', 'messages');
    }
}
