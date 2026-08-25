<?php

namespace Modules\Zatca\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\General\Models\Transaction;
use Modules\Zatca\Http\Requests\UpdateZatcaOperationsRequest;
use Modules\Zatca\Http\Requests\UpdateZatcaSettingRequest;
use Modules\Zatca\Models\ZatcaInvoiceSync;
use Modules\Zatca\Models\ZatcaSetting;
use Modules\Zatca\Services\ZatcaCredentialService;
use Modules\Zatca\Services\ZatcaOperationsService;
use Modules\Zatca\Services\ZatcaSellSyncService;
use Modules\Zatca\Services\ZatcaSetupReadinessService;
use Throwable;

class ZatcaSettingController extends Controller
{
    public function __construct(
        private readonly ZatcaCredentialService $credentials,
        private readonly ZatcaSellSyncService $sellSync,
        private readonly ZatcaSetupReadinessService $readiness,
        private readonly ZatcaOperationsService $operations
    ) {}

    public function edit(Request $request): View
    {
        abort_unless(config('zatca.show_in_menu', true), 404);

        $setting = ZatcaSetting::current();
        $perPage = 15;

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

        return view('zatca::settings.edit', [
            'setting' => $setting,
            'readiness' => $this->readiness->analyze($setting),
            'sellInvoices' => $sellInvoices,
            'syncMap' => $syncMap,
            'statusFilter' => $statusFilter,
            'statusCounts' => $statusCounts,
            'sellReturns' => $sellReturns,
            'returnSyncMap' => $returnSyncMap,
            'parentSyncMap' => $parentSyncMap,
            'returnStatusFilter' => $returnStatusFilter,
            'returnStatusCounts' => $returnStatusCounts,
            'sandboxCounts' => $this->operations->sandboxCounts(),
            'environments' => [
                'local' => __('zatca::lang.env_local'),
                'simulation' => __('zatca::lang.env_simulation'),
                'production' => __('zatca::lang.env_production'),
            ],
            'invoiceTypes' => [
                '0100' => __('zatca::lang.invoice_type_simplified'),
                '1000' => __('zatca::lang.invoice_type_standard'),
                '1100' => __('zatca::lang.invoice_type_both'),
            ],
        ]);
    }

    private function applyZatcaStatusFilter($query, string $statusFilter): void
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

    public function update(UpdateZatcaSettingRequest $request): RedirectResponse
    {
        abort_unless(config('zatca.show_in_menu', true), 404);

        $setting = ZatcaSetting::current();
        $generate = $request->boolean('generate_certificates', true);

        try {
            $result = $this->credentials->save($setting, $request->validated(), $generate);

            return redirect()
                ->route('zatca.settings.edit', ['tab' => 'connection'])
                ->with('success', $result['message'])
                ->with('active_tab', 'connection');
        } catch (Throwable $e) {
            return redirect()
                ->route('zatca.settings.edit', ['tab' => 'connection'])
                ->withInput()
                ->with('error', $e->getMessage())
                ->with('active_tab', 'connection');
        }
    }

    public function regenerate(UpdateZatcaSettingRequest $request): RedirectResponse
    {
        abort_unless(config('zatca.show_in_menu', true), 404);

        $setting = ZatcaSetting::current();

        try {
            $this->credentials->save($setting, $request->validated(), false);
            $result = $this->credentials->generateAndPersist($setting->fresh());

            return redirect()
                ->route('zatca.settings.edit', ['tab' => 'connection'])
                ->with('success', $result['message'])
                ->with('active_tab', 'connection');
        } catch (Throwable $e) {
            return redirect()
                ->route('zatca.settings.edit', ['tab' => 'connection'])
                ->withInput()
                ->with('error', $e->getMessage())
                ->with('active_tab', 'connection');
        }
    }

    /**
     * Sync one or many sell invoices to ZATCA.
     */
    public function syncSell(Request $request): RedirectResponse
    {
        abort_unless(config('zatca.show_in_menu', true), 404);

        $setting = ZatcaSetting::current();

        if (! $setting->isConfigured()) {
            return redirect()
                ->route('zatca.settings.edit', ['tab' => 'send'])
                ->with('error', __('zatca::lang.send_requires_credentials'))
                ->with('active_tab', 'send');
        }

        $validated = $request->validate([
            'transaction_ids' => ['required', 'array', 'min:1'],
            'transaction_ids.*' => ['integer'],
            'report_types' => ['nullable', 'array'],
            'report_types.*' => ['nullable', 'in:B2C,B2B'],
            'default_report_type' => ['nullable', 'in:B2C,B2B'],
        ]);

        $defaultType = $validated['default_report_type'] ?? 'B2C';
        $reportTypes = $validated['report_types'] ?? [];

        $items = collect($validated['transaction_ids'])
            ->unique()
            ->map(fn ($id) => [
                'id' => (int) $id,
                'report_type' => $reportTypes[$id] ?? $reportTypes[(string) $id] ?? $defaultType,
            ])
            ->values()
            ->all();

        $result = $this->sellSync->syncMany($items, $setting);

        $activeTab = (string) $request->input('active_tab', 'send');
        if (! in_array($activeTab, ['send', 'returns'], true)) {
            // Infer from first synced document type when possible.
            $firstId = (int) ($items[0]['id'] ?? 0);
            $type = $firstId
                ? Transaction::query()->whereKey($firstId)->value('type')
                : null;
            $activeTab = $type === 'sell-return' ? 'returns' : 'send';
        }

        $redirect = redirect()
            ->route('zatca.settings.edit', ['tab' => $activeTab])
            ->with('active_tab', $activeTab)
            ->with('sync_feedback', [
                'success' => $result['success'],
                'failed' => $result['failed'],
                'items' => $result['feedback'] ?? [],
            ]);

        if ($result['failed'] > 0 && $result['success'] === 0) {
            return $redirect->with('error', __('zatca::lang.sync_batch_summary', [
                'success' => $result['success'],
                'failed' => $result['failed'],
            ]));
        }

        if ($result['failed'] > 0) {
            return $redirect->with('error', __('zatca::lang.sync_batch_summary', [
                'success' => $result['success'],
                'failed' => $result['failed'],
            ]));
        }

        return $redirect->with('success', __('zatca::lang.sync_batch_summary', [
            'success' => $result['success'],
            'failed' => $result['failed'],
        ]));
    }

    public function updateOperations(UpdateZatcaOperationsRequest $request): RedirectResponse
    {
        abort_unless(config('zatca.show_in_menu', true), 404);

        $setting = ZatcaSetting::current();
        $this->operations->updateOperations($setting, $request->validated());

        return redirect()
            ->route('zatca.settings.edit', ['tab' => 'operations'])
            ->with('success', __('zatca::lang.ops_saved'))
            ->with('active_tab', 'operations');
    }

    public function purgeSandbox(Request $request): RedirectResponse
    {
        abort_unless(config('zatca.show_in_menu', true), 404);

        $request->validate([
            'confirm' => ['required', 'in:1,true,yes'],
        ]);

        try {
            $result = $this->operations->purgeSandboxSyncs(ZatcaSetting::current());

            return redirect()
                ->route('zatca.settings.edit', ['tab' => 'operations'])
                ->with('success', __('zatca::lang.ops_purge_success', ['count' => $result['deleted']]))
                ->with('active_tab', 'operations');
        } catch (Throwable $e) {
            return redirect()
                ->route('zatca.settings.edit', ['tab' => 'operations'])
                ->with('error', $e->getMessage())
                ->with('active_tab', 'operations');
        }
    }
}
