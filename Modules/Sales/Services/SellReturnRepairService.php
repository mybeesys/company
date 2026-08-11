<?php

namespace Modules\Sales\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionPayments;
use Modules\General\Models\TransactionSellLine;
use Modules\General\Support\TransactionLineTaxRate;
use Modules\Inventory\Models\InventoryCostMovement;
use Modules\Sales\Http\Controllers\SellReturnController;

/**
 * Rebuild broken sell-return documents created before discount / warehouse / AR fixes.
 */
class SellReturnRepairService
{
    /**
     * @return array{
     *   id:int,
     *   ref_no:?string,
     *   changes:list<string>,
     *   before:array<string,mixed>,
     *   after:array<string,mixed>,
     *   skipped:?string
     * }
     */
    public function preview(Transaction $returnTx): array
    {
        return $this->repairOne($returnTx, false);
    }

    /**
     * @return array{
     *   id:int,
     *   ref_no:?string,
     *   changes:list<string>,
     *   before:array<string,mixed>,
     *   after:array<string,mixed>,
     *   skipped:?string
     * }
     */
    public function execute(Transaction $returnTx): array
    {
        return $this->repairOne($returnTx, true);
    }

    /**
     * @return array{
     *   id:int,
     *   ref_no:?string,
     *   changes:list<string>,
     *   before:array<string,mixed>,
     *   after:array<string,mixed>,
     *   skipped:?string
     * }
     */
    private function repairOne(Transaction $returnTx, bool $persist): array
    {
        if ($returnTx->type !== 'sell-return') {
            return $this->result($returnTx, [], [], [], 'Not a sell-return');
        }

        $parent = Transaction::query()
            ->with([
                'sell_lines' => fn ($q) => $q->where('is_show', 1)
                    ->where(function ($query) {
                        $query->whereNull('parent_id')
                            ->orWhere('parent_id', '')
                            ->orWhere('parent_id', 0);
                    })
                    ->orderBy('id'),
            ])
            ->find($returnTx->parent_id);

        if (! $parent || $parent->type !== 'sell') {
            return $this->result($returnTx, [], [], [], 'Missing parent sell invoice');
        }

        $returnTx->load('purchases_lines');
        if ($returnTx->purchases_lines->isEmpty()) {
            return $this->result($returnTx, [], [], [], 'No return lines');
        }

        $before = $this->snapshot($returnTx);
        $changes = [];

        $establishmentId = $returnTx->establishment_id ?: $parent->establishment_id;
        $invoiceType = $returnTx->invoice_type ?: $parent->invoice_type ?: 'due';

        if ((int) $returnTx->establishment_id !== (int) $establishmentId) {
            $changes[] = "establishment_id: {$returnTx->establishment_id} → {$establishmentId}";
        }
        if ((string) $returnTx->invoice_type !== (string) $invoiceType) {
            $changes[] = "invoice_type: {$returnTx->invoice_type} → {$invoiceType}";
        }

        $linePlan = $this->rebuildLinesFromParent($returnTx->purchases_lines, $parent->sell_lines);
        foreach ($linePlan as $row) {
            if ($row['changed']) {
                $changes[] = sprintf(
                    'line#%d product %s: disc %s/%s → %s/%s | before_vat %s → %s | vat %s → %s | after %s → %s',
                    $row['id'],
                    $row['product_id'],
                    $row['old_discount_amount'],
                    $row['old_discount_type'] ?: '-',
                    $row['discount_amount'],
                    $row['discount_type'] ?: '-',
                    $row['old_total_before_vat'],
                    $row['total_before_vat'],
                    $row['old_tax_value'],
                    $row['tax_value'],
                    $row['old_unit_price_inc_tax'],
                    $row['unit_price_inc_tax']
                );
            }
        }

        $totals = $this->rebuildHeaderTotals($linePlan, $parent);
        if (abs((float) $returnTx->total_before_tax - $totals['total_before_tax']) > 0.009) {
            $changes[] = "total_before_tax: {$returnTx->total_before_tax} → {$totals['total_before_tax']}";
        }
        if (abs((float) $returnTx->discount_amount - $totals['discount_amount']) > 0.009
            || (string) ($returnTx->discount_type ?? '') !== (string) ($totals['discount_type'] ?? '')) {
            $changes[] = sprintf(
                'invoice_discount: %s/%s → %s/%s',
                $returnTx->discount_amount,
                $returnTx->discount_type ?: '-',
                $totals['discount_amount'],
                $totals['discount_type'] ?: '-'
            );
        }
        if (abs((float) $returnTx->totalAfterDiscount - $totals['totalAfterDiscount']) > 0.009) {
            $changes[] = "totalAfterDiscount: {$returnTx->totalAfterDiscount} → {$totals['totalAfterDiscount']}";
        }
        if (abs((float) $returnTx->tax_amount - $totals['tax_amount']) > 0.009) {
            $changes[] = "tax_amount: {$returnTx->tax_amount} → {$totals['tax_amount']}";
        }
        if (abs((float) $returnTx->final_total - $totals['final_total']) > 0.009) {
            $changes[] = "final_total: {$returnTx->final_total} → {$totals['final_total']}";
        }

        $changes[] = 'rebuild auto journal + refresh inventory costing movements (if any)';

        if (! $persist) {
            return $this->result($returnTx, $changes, $before, array_merge($totals, [
                'establishment_id' => $establishmentId,
                'invoice_type' => $invoiceType,
            ]), null);
        }

        DB::transaction(function () use ($returnTx, $parent, $establishmentId, $invoiceType, $linePlan, $totals) {
            foreach ($linePlan as $row) {
                TransactionePurchasesLine::where('id', $row['id'])->update([
                    'unit_price' => $row['unit_price'],
                    'unit_price_before_discount' => $row['unit_price'],
                    'discount_type' => $row['discount_type'],
                    'discount_amount' => $row['discount_amount'],
                    'tax_id' => $row['tax_id'],
                    'tax_value' => $row['tax_value'],
                    'total_before_vat' => $row['total_before_vat'],
                    'unit_price_inc_tax' => $row['unit_price_inc_tax'],
                ]);
            }

            $returnTx->establishment_id = $establishmentId;
            $returnTx->invoice_type = $invoiceType;
            $returnTx->discount_amount = $totals['discount_amount'];
            $returnTx->discount_type = $totals['discount_type'];
            $returnTx->total_before_tax = $totals['total_before_tax'];
            $returnTx->totalAfterDiscount = $totals['totalAfterDiscount'];
            $returnTx->tax_amount = $totals['tax_amount'];
            $returnTx->final_total = $totals['final_total'];
            $returnTx->save();

            $this->purgeAutoAccounting($returnTx);
            InventoryCostMovement::query()->where('transaction_id', $returnTx->id)->delete();

            $payment = TransactionPayments::create([
                'transaction_id' => $returnTx->id,
                'payment_type' => $returnTx->invoice_type,
                'amount' => $returnTx->final_total,
                'method' => $returnTx->invoice_type === 'cash' ? 'cash' : 'due',
                'is_return' => 1,
                'paid_on' => $returnTx->transaction_date ?? now(),
                'created_by' => $returnTx->created_by,
                'payment_for' => $returnTx->contact_id,
                'payment_ref_no' => 'REPAIR-'.$returnTx->ref_no,
                'account_id' => null,
            ]);

            $accountUtil = new AccountingUtil;
            // Re-run costing then post corrected journal (accounts_route also calls processTransaction).
            $accountUtil->accounts_route($payment, $returnTx->fresh('purchases_lines'), null, null, request());

            app(SellReturnController::class)->updateSalesReturnStatus((int) $parent->id);
        });

        $returnTx->refresh();

        return $this->result($returnTx, $changes, $before, $this->snapshot($returnTx), null);
    }

    /**
     * @param  Collection<int, TransactionePurchasesLine>  $returnLines
     * @param  Collection<int, TransactionSellLine>  $sellLines
     * @return list<array<string, mixed>>
     */
    private function rebuildLinesFromParent(Collection $returnLines, Collection $sellLines): array
    {
        $remainingBySellLine = [];
        foreach ($sellLines as $sellLine) {
            $remainingBySellLine[(int) $sellLine->id] = (float) $sellLine->qyt;
        }

        $plan = [];
        foreach ($returnLines as $returnLine) {
            $qty = (float) $returnLine->qyt;
            $productId = (int) $returnLine->product_id;
            $candidates = $sellLines->where('product_id', $productId)->values();

            $allocatedDiscMoney = 0.0;
            $unitPrice = (float) $returnLine->unit_price;
            $discountType = $returnLine->discount_type;
            $discountAmount = (float) ($returnLine->discount_amount ?? 0);
            $taxId = $returnLine->tax_id;
            $taxRate = $this->taxRatePercent($taxId);
            $remainingQty = $qty;

            if ($candidates->isNotEmpty()) {
                $unitPrice = (float) $candidates->first()->unit_price;
                $taxId = $candidates->first()->tax_id;
                $taxRate = $this->taxRatePercent($taxId);

                // Prefer percent discount if any matched sell line uses percent; else fixed scaled money.
                $usesPercent = $candidates->contains(fn ($l) => ($l->discount_type ?? '') === 'percent');
                if ($usesPercent) {
                    $discountType = 'percent';
                    $discountAmount = (float) ($candidates->firstWhere('discount_type', 'percent')->discount_amount ?? 0);
                } else {
                    $discountType = 'fixed';
                    foreach ($candidates as $sellLine) {
                        if ($remainingQty <= 0) {
                            break;
                        }
                        $sellId = (int) $sellLine->id;
                        $available = $remainingBySellLine[$sellId] ?? 0.0;
                        if ($available <= 0) {
                            continue;
                        }
                        $take = min($available, $remainingQty);
                        $sellQty = max(0.0001, (float) $sellLine->qyt);
                        $sellDisc = (float) ($sellLine->discount_amount ?? 0);
                        $allocatedDiscMoney += $sellDisc * ($take / $sellQty);
                        $remainingBySellLine[$sellId] = $available - $take;
                        $remainingQty -= $take;
                    }
                    $discountAmount = round($allocatedDiscMoney, 4);
                }
            }

            $lineGross = round($qty * $unitPrice, 4);
            if ($discountType === 'percent') {
                $discMoney = round($lineGross * ((float) $discountAmount / 100), 4);
            } else {
                $discMoney = round((float) $discountAmount, 4);
                $discountType = $discMoney > 0 ? 'fixed' : null;
            }
            $totalBeforeVat = max(0, round($lineGross - $discMoney, 4));
            $taxValue = round($totalBeforeVat * ($taxRate / 100), 4);
            $totalAfterVat = round($totalBeforeVat + $taxValue, 4);

            $changed = abs((float) $returnLine->discount_amount - (float) $discountAmount) > 0.0001
                || (string) ($returnLine->discount_type ?? '') !== (string) ($discountType ?? '')
                || abs((float) $returnLine->total_before_vat - $totalBeforeVat) > 0.009
                || abs((float) $returnLine->tax_value - $taxValue) > 0.009
                || abs((float) $returnLine->unit_price_inc_tax - $totalAfterVat) > 0.009
                || abs((float) $returnLine->unit_price - $unitPrice) > 0.0001;

            $plan[] = [
                'id' => (int) $returnLine->id,
                'product_id' => $productId,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'discount_type' => $discountType,
                'discount_amount' => $discountAmount,
                'tax_id' => $taxId,
                'tax_rate' => $taxRate,
                'tax_value' => $taxValue,
                'total_before_vat' => $totalBeforeVat,
                'unit_price_inc_tax' => $totalAfterVat,
                'old_discount_type' => $returnLine->discount_type,
                'old_discount_amount' => $returnLine->discount_amount,
                'old_total_before_vat' => $returnLine->total_before_vat,
                'old_tax_value' => $returnLine->tax_value,
                'old_unit_price_inc_tax' => $returnLine->unit_price_inc_tax,
                'changed' => $changed,
            ];
        }

        return $plan;
    }

    /**
     * @param  list<array<string, mixed>>  $linePlan
     * @return array{
     *   total_before_tax: float,
     *   discount_amount: float,
     *   discount_type: ?string,
     *   totalAfterDiscount: float,
     *   tax_amount: float,
     *   final_total: float
     * }
     */
    private function rebuildHeaderTotals(array $linePlan, Transaction $parent): array
    {
        $totalBeforeVat = round(array_sum(array_column($linePlan, 'total_before_vat')), 2);

        $parentBefore = (float) ($parent->total_before_tax ?? 0);
        $parentAfter = (float) ($parent->totalAfterDiscount ?? $parent->total_after_discount ?? $parentBefore);
        $parentDiscMoney = round(max(0, $parentBefore - $parentAfter), 2);
        if ($parentDiscMoney <= 0) {
            $raw = (float) ($parent->discount_amount ?? 0);
            if (($parent->discount_type ?? '') === 'percent') {
                $parentDiscMoney = round($parentBefore * ($raw / 100), 2);
            } else {
                $parentDiscMoney = round(max(0, $raw), 2);
            }
        }

        $invoiceDisc = 0.0;
        $invoiceDiscType = null;
        if ($parentDiscMoney > 0 && $parentBefore > 0 && $totalBeforeVat > 0) {
            $invoiceDisc = round($parentDiscMoney * min(1, $totalBeforeVat / $parentBefore), 2);
            $invoiceDiscType = 'fixed';
        }

        $totalAfterDiscount = round(max(0, $totalBeforeVat - $invoiceDisc), 2);

        $taxAmount = 0.0;
        foreach ($linePlan as $row) {
            $share = $totalBeforeVat > 0
                ? ((float) $row['total_before_vat'] / $totalBeforeVat) * $invoiceDisc
                : 0.0;
            $adjustedNet = max(0, (float) $row['total_before_vat'] - $share);
            $taxAmount += $adjustedNet * (((float) $row['tax_rate']) / 100);
        }
        $taxAmount = round($taxAmount, 2);
        $finalTotal = round($totalAfterDiscount + $taxAmount, 2);

        return [
            'total_before_tax' => $totalBeforeVat,
            'discount_amount' => $invoiceDisc,
            'discount_type' => $invoiceDiscType,
            'totalAfterDiscount' => $totalAfterDiscount,
            'tax_amount' => $taxAmount,
            'final_total' => $finalTotal,
        ];
    }

    private function taxRatePercent(mixed $taxId): float
    {
        $raw = TransactionLineTaxRate::displayPercent($taxId !== null ? (string) $taxId : null);
        if ($raw === '--' || ! is_numeric($raw)) {
            return 0.0;
        }

        return (float) $raw;
    }

    private function purgeAutoAccounting(Transaction $returnTx): void
    {
        $mappingIds = AccountingAccountsTransaction::query()
            ->where('transaction_id', $returnTx->id)
            ->whereNotNull('acc_trans_mapping_id')
            ->pluck('acc_trans_mapping_id')
            ->unique()
            ->filter()
            ->values();

        AccountingAccountsTransaction::query()
            ->where('transaction_id', $returnTx->id)
            ->delete();

        TransactionPayments::query()
            ->where('transaction_id', $returnTx->id)
            ->delete();

        if ($mappingIds->isNotEmpty()) {
            // Remove auto journals that are now empty.
            foreach ($mappingIds as $mappingId) {
                $stillUsed = AccountingAccountsTransaction::query()
                    ->where('acc_trans_mapping_id', $mappingId)
                    ->exists();
                if (! $stillUsed) {
                    AccountingAccTransMapping::query()
                        ->where('id', $mappingId)
                        ->where(function ($q) {
                            $q->where('is_manual', 0)->orWhereNull('is_manual');
                        })
                        ->delete();
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Transaction $tx): array
    {
        return [
            'establishment_id' => $tx->establishment_id,
            'invoice_type' => $tx->invoice_type,
            'discount_amount' => $tx->discount_amount,
            'discount_type' => $tx->discount_type,
            'total_before_tax' => $tx->total_before_tax,
            'totalAfterDiscount' => $tx->totalAfterDiscount,
            'tax_amount' => $tx->tax_amount,
            'final_total' => $tx->final_total,
        ];
    }

    /**
     * @param  list<string>  $changes
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{
     *   id:int,
     *   ref_no:?string,
     *   changes:list<string>,
     *   before:array<string,mixed>,
     *   after:array<string,mixed>,
     *   skipped:?string
     * }
     */
    private function result(Transaction $tx, array $changes, array $before, array $after, ?string $skipped): array
    {
        return [
            'id' => (int) $tx->id,
            'ref_no' => $tx->ref_no,
            'changes' => $changes,
            'before' => $before,
            'after' => $after,
            'skipped' => $skipped,
        ];
    }
}
