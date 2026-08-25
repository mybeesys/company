<?php

namespace Modules\Zatca\Services;

use Modules\General\Models\Transaction;
use Modules\Zatca\Models\ZatcaInvoiceSync;
use Modules\Zatca\Models\ZatcaSetting;
use RuntimeException;

class ZatcaInvoiceGuard
{
    /**
     * Original sell invoice is locked against destructive edit/delete after ZATCA sync.
     * Credit notes (sell-return) remain allowed — they are the compliant reversal path.
     */
    public function isLocked(Transaction $transaction): bool
    {
        if ((string) $transaction->type !== 'sell') {
            return false;
        }

        $setting = ZatcaSetting::current();
        if (! (bool) $setting->lock_synced_invoices) {
            return false;
        }

        return ZatcaInvoiceSync::query()
            ->where('transaction_id', $transaction->id)
            ->where('status', ZatcaInvoiceSync::STATUS_SYNCED)
            ->exists();
    }

    public function assertMutable(Transaction $transaction): void
    {
        if ($this->isLocked($transaction)) {
            throw new RuntimeException(__('zatca::lang.ops_invoice_locked', [
                'ref' => $transaction->ref_no,
            ]));
        }
    }

    /**
     * Credit notes must reference a ZATCA-synced parent invoice.
     */
    public function assertParentSyncedForReturn(Transaction $parentSell): void
    {
        if ((string) $parentSell->type !== 'sell') {
            throw new RuntimeException(__('zatca::lang.credit_note_parent_missing', [
                'ref' => $parentSell->ref_no,
            ]));
        }

        if (! config('zatca.show_in_menu', true)) {
            return;
        }

        $setting = ZatcaSetting::current();
        if (! $setting->isConfigured()) {
            return;
        }

        $synced = ZatcaInvoiceSync::query()
            ->where('transaction_id', $parentSell->id)
            ->where('status', ZatcaInvoiceSync::STATUS_SYNCED)
            ->exists();

        if (! $synced) {
            throw new RuntimeException(__('zatca::lang.credit_note_parent_not_synced', [
                'ref' => $parentSell->ref_no,
            ]));
        }
    }
}
