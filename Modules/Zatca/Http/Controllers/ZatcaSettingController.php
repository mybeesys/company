<?php

namespace Modules\Zatca\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\General\Models\Transaction;
use Modules\Zatca\Http\Controllers\Concerns\LoadsZatcaDocumentListings;
use Modules\Zatca\Http\Requests\UpdateZatcaOperationsRequest;
use Modules\Zatca\Http\Requests\UpdateZatcaSettingRequest;
use Modules\Zatca\Models\ZatcaSetting;
use Modules\Zatca\Services\ZatcaCompanyDefaultsService;
use Modules\Zatca\Services\ZatcaCredentialService;
use Modules\Zatca\Services\ZatcaOperationsService;
use Modules\Zatca\Services\ZatcaSellSyncService;
use Modules\Zatca\Services\ZatcaSetupReadinessService;
use Throwable;

class ZatcaSettingController extends Controller
{
    use LoadsZatcaDocumentListings;

    public function __construct(
        private readonly ZatcaCredentialService $credentials,
        private readonly ZatcaSellSyncService $sellSync,
        private readonly ZatcaSetupReadinessService $readiness,
        private readonly ZatcaOperationsService $operations,
        private readonly ZatcaCompanyDefaultsService $companyDefaults
    ) {}

    public function edit(Request $request): View|RedirectResponse
    {
        abort_unless(config('zatca.show_in_menu', true), 404);

        $requestedTab = (string) $request->query('tab', session('active_tab', 'connection'));
        if (in_array($requestedTab, ['send', 'returns'], true)) {
            return redirect()->route('zatca.einvoicing.index', array_filter([
                'tab' => $requestedTab,
                'zatca_status' => $request->query('zatca_status'),
                'zatca_return_status' => $request->query('zatca_return_status'),
                'sell_page' => $request->query('sell_page'),
                'return_page' => $request->query('return_page'),
            ]));
        }

        $setting = ZatcaSetting::current();
        $companyDefaults = $this->companyDefaults->forCurrentCompany();
        $mergedForm = $this->companyDefaults->mergeForForm($setting, $companyDefaults['values']);

        // Readiness preview includes company-backed empty fields so the checklist stays accurate.
        $readinessSetting = $setting->replicate();
        foreach ($mergedForm['values'] as $key => $value) {
            if (trim((string) ($setting->{$key} ?? '')) === '' && $value !== '') {
                $readinessSetting->{$key} = $value;
            }
        }

        return view('zatca::settings.edit', [
            'setting' => $setting,
            'formValues' => $mergedForm['values'],
            'appliedFromCompany' => $mergedForm['applied_from_company'],
            'companyDefaults' => $companyDefaults,
            'readiness' => $this->readiness->analyze($readinessSetting),
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
     * Sync one or many sell invoices / returns to ZATCA.
     */
    public function syncSell(Request $request): RedirectResponse
    {
        abort_unless(config('zatca.show_in_menu', true), 404);

        $setting = ZatcaSetting::current();

        if (! $setting->isConfigured()) {
            return redirect()
                ->route('zatca.einvoicing.index', ['tab' => 'send'])
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
            $firstId = (int) ($items[0]['id'] ?? 0);
            $type = $firstId
                ? Transaction::query()->whereKey($firstId)->value('type')
                : null;
            $activeTab = $type === 'sell-return' ? 'returns' : 'send';
        }

        $redirect = redirect()
            ->route('zatca.einvoicing.index', ['tab' => $activeTab])
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
