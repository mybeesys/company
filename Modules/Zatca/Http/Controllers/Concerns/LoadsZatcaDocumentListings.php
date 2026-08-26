<?php

namespace Modules\Zatca\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\General\Models\Transaction;
use Modules\Zatca\Models\ZatcaInvoiceSync;

trait LoadsZatcaDocumentListings
{
    /**
     * @return array{
     *     sellInvoices: \Illuminate\Contracts\Pagination\LengthAwarePaginator,
     *     syncMap: \Illuminate\Support\Collection,
     *     statusFilter: string,
     *     statusCounts: array<string, int>
     * }
     */
    protected function loadSellInvoiceListing(Request $request, int $perPage = 15): array
    {
        $statusFilter = (string) $request->query('zatca_status', 'all');
        if (! in_array($statusFilter, ['all', 'pending', 'synced', 'failed'], true)) {
            $statusFilter = 'all';
        }

        $sellBase = Transaction::query()
            ->where('type', 'sell')
            ->whereIn('status', ['approved', 'final']);

        $statusCounts = [
            'all' => (clone $sellBase)->count(),
            'synced' => (clone $sellBase)->whereHas('zatcaInvoiceSync', fn ($q) => $q->where('status', ZatcaInvoiceSync::STATUS_SYNCED))->count(),
            'failed' => (clone $sellBase)->whereHas('zatcaInvoiceSync', fn ($q) => $q->where('status', ZatcaInvoiceSync::STATUS_FAILED))->count(),
            'pending' => 0,
        ];
        $statusCounts['pending'] = max(0, $statusCounts['all'] - $statusCounts['synced'] - $statusCounts['failed']);

        $sellQuery = (clone $sellBase)
            ->with(['client:id,name'])
            ->latest('id');

        $this->applyZatcaStatusFilter($sellQuery, $statusFilter);

        $sellInvoices = $sellQuery->paginate($perPage, [
            'id', 'ref_no', 'transaction_date', 'final_total', 'contact_id', 'status', 'payment_status',
        ], 'sell_page')->withQueryString();

        $syncMap = ZatcaInvoiceSync::query()
            ->whereIn('transaction_id', $sellInvoices->getCollection()->pluck('id'))
            ->get()
            ->keyBy('transaction_id');

        return compact('sellInvoices', 'syncMap', 'statusFilter', 'statusCounts');
    }

    /**
     * @return array{
     *     sellReturns: \Illuminate\Contracts\Pagination\LengthAwarePaginator,
     *     returnSyncMap: \Illuminate\Support\Collection,
     *     parentSyncMap: \Illuminate\Support\Collection,
     *     returnStatusFilter: string,
     *     returnStatusCounts: array<string, int>
     * }
     */
    protected function loadSellReturnListing(Request $request, int $perPage = 15): array
    {
        $returnStatusFilter = (string) $request->query('zatca_return_status', 'all');
        if (! in_array($returnStatusFilter, ['all', 'pending', 'synced', 'failed'], true)) {
            $returnStatusFilter = 'all';
        }

        $returnBase = Transaction::query()
            ->where('type', 'sell-return')
            ->whereIn('status', ['approved', 'final']);

        $returnStatusCounts = [
            'all' => (clone $returnBase)->count(),
            'synced' => (clone $returnBase)->whereHas('zatcaInvoiceSync', fn ($q) => $q->where('status', ZatcaInvoiceSync::STATUS_SYNCED))->count(),
            'failed' => (clone $returnBase)->whereHas('zatcaInvoiceSync', fn ($q) => $q->where('status', ZatcaInvoiceSync::STATUS_FAILED))->count(),
            'pending' => 0,
        ];
        $returnStatusCounts['pending'] = max(0, $returnStatusCounts['all'] - $returnStatusCounts['synced'] - $returnStatusCounts['failed']);

        $returnQuery = (clone $returnBase)
            ->with(['client:id,name', 'parentSell:id,ref_no,contact_id'])
            ->latest('id');

        $this->applyZatcaStatusFilter($returnQuery, $returnStatusFilter);

        $sellReturns = $returnQuery->paginate($perPage, [
            'id', 'ref_no', 'transaction_date', 'final_total', 'contact_id', 'status', 'parent_id',
        ], 'return_page')->withQueryString();

        $returnSyncMap = ZatcaInvoiceSync::query()
            ->whereIn('transaction_id', $sellReturns->getCollection()->pluck('id'))
            ->get()
            ->keyBy('transaction_id');

        $parentSyncMap = ZatcaInvoiceSync::query()
            ->whereIn('transaction_id', $sellReturns->getCollection()->pluck('parent_id')->filter()->unique())
            ->get()
            ->keyBy('transaction_id');

        return compact('sellReturns', 'returnSyncMap', 'parentSyncMap', 'returnStatusFilter', 'returnStatusCounts');
    }

    protected function applyZatcaStatusFilter(Builder $query, string $statusFilter): void
    {
        if ($statusFilter === 'synced') {
            $query->whereHas('zatcaInvoiceSync', fn ($q) => $q->where('status', ZatcaInvoiceSync::STATUS_SYNCED));
        } elseif ($statusFilter === 'failed') {
            $query->whereHas('zatcaInvoiceSync', fn ($q) => $q->where('status', ZatcaInvoiceSync::STATUS_FAILED));
        } elseif ($statusFilter === 'pending') {
            $query->where(function ($q) {
                $q->whereDoesntHave('zatcaInvoiceSync')
                    ->orWhereHas('zatcaInvoiceSync', fn ($s) => $s->where('status', ZatcaInvoiceSync::STATUS_PENDING));
            });
        }
    }
}
