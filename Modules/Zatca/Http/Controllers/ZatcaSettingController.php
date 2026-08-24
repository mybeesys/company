<?php

namespace Modules\Zatca\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\General\Models\Transaction;
use Modules\Zatca\Http\Requests\UpdateZatcaSettingRequest;
use Modules\Zatca\Models\ZatcaInvoiceSync;
use Modules\Zatca\Models\ZatcaSetting;
use Modules\Zatca\Services\ZatcaCredentialService;
use Modules\Zatca\Services\ZatcaSellSyncService;
use Throwable;

class ZatcaSettingController extends Controller
{
    public function __construct(
        private readonly ZatcaCredentialService $credentials,
        private readonly ZatcaSellSyncService $sellSync
    ) {}

    public function edit(Request $request): View
    {
        abort_unless(config('zatca.show_in_menu', true), 404);

        $setting = ZatcaSetting::current();
        $statusFilter = (string) $request->query('zatca_status', 'all');
        if (! in_array($statusFilter, ['all', 'pending', 'synced', 'failed'], true)) {
            $statusFilter = 'all';
        }

        $sellQuery = Transaction::query()
            ->where('type', 'sell')
            ->whereIn('status', ['approved', 'final'])
            ->with(['client:id,name'])
            ->latest('id');

        $allSellIds = (clone $sellQuery)->limit(500)->pluck('id');
        $syncMap = ZatcaInvoiceSync::query()
            ->whereIn('transaction_id', $allSellIds)
            ->get()
            ->keyBy('transaction_id');

        $statusCounts = [
            'all' => $allSellIds->count(),
            'pending' => 0,
            'synced' => 0,
            'failed' => 0,
        ];

        foreach ($allSellIds as $id) {
            $status = $syncMap->get($id)?->status ?? ZatcaInvoiceSync::STATUS_PENDING;
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }
        }

        $sellInvoices = $sellQuery->limit(200)->get([
            'id', 'ref_no', 'transaction_date', 'final_total', 'contact_id', 'status', 'payment_status',
        ]);

        if ($statusFilter !== 'all') {
            $sellInvoices = $sellInvoices->filter(function (Transaction $invoice) use ($syncMap, $statusFilter) {
                $status = $syncMap->get($invoice->id)?->status ?? ZatcaInvoiceSync::STATUS_PENDING;

                return $status === $statusFilter;
            })->values();
        }

        return view('zatca::settings.edit', [
            'setting' => $setting,
            'sellInvoices' => $sellInvoices,
            'syncMap' => $syncMap,
            'statusFilter' => $statusFilter,
            'statusCounts' => $statusCounts,
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

        $message = __('zatca::lang.sync_batch_summary', [
            'success' => $result['success'],
            'failed' => $result['failed'],
        ]);

        $redirect = redirect()
            ->route('zatca.settings.edit', ['tab' => 'send'])
            ->with('active_tab', 'send');

        if ($result['failed'] > 0 && $result['success'] === 0) {
            return $redirect->with('error', $message);
        }

        if ($result['failed'] > 0) {
            return $redirect->with('error', $message);
        }

        return $redirect->with('success', $message);
    }
}
