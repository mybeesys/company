<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB as FacadesDB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Employee\Models\Employee;
use Modules\General\Models\Country;
use Modules\General\Models\NotificationSetting;
use Modules\General\Models\NotificationSettingParameter;
use Modules\General\Models\PrefixSetting;
use Modules\General\Models\Setting;
use Modules\General\Models\Tax;
use Modules\Product\Models\UnitTransfer;

class GeneralController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('general::index');
    }

    public function storeSidebarState(Request $request)
    {
        $state = $request->input('state') === 'true' ? true : false;

        session(['sidebar_minimize' => $state]);

        return response()->json(['success' => true]);
    }

    public function setting(Request $request)
    {
        if (! get_company_id()) {
            return redirect()->back()->with('error', __('establishment::responses.no_company_found'));
        }

        $gate = app(\App\Services\EntitlementGate::class);

        $cards = [
            [
                'name' => __('menuItemLang.taxes'),
                'route' => 'taxes',
                'icon' => 'ki-outline fas fa-percent',
            ],
        ];

        $taxesColumns = [];
        $taxes = collect();
        if ($gate->settingAllowed('taxes')) {
            try {
                $taxesColumns = Tax::getsTaxesColumns();
                $taxes = Tax::where('is_tax_group', 0)->get();
            } catch (\Throwable) {
                // keep empty
            }
        }

        $methodColumns = [];

        $employees = collect();
        $notifications_settings = collect();
        if ($gate->settingAllowed('notifications')) {
            try {
                $employees = Employee::where('pos_is_active', true)->select('name', 'name_en', 'id')->get();
            } catch (\Throwable) {
                $employees = collect();
            }
            try {
                $notifications_settings = NotificationSetting::all();
            } catch (\Throwable) {
                $notifications_settings = collect();
            }
        }

        $notifications_settings_parameters = collect();
        if ($gate->settingAllowed('mail_settings') || $gate->settingAllowed('sms_settings')) {
            try {
                $notifications_settings_parameters = NotificationSettingParameter::all();
            } catch (\Throwable) {
                $notifications_settings_parameters = collect();
            }
        }

        $prefixes = collect();
        $prefixes_mapp = collect();
        $prefixes_payments = collect();
        if ($gate->settingAllowed('prefix_settings')) {
            try {
                $prefixes = $gate->filterPrefixes(PrefixSetting::where('table_name', 'transactions')->get());
                $prefixes_mapp = $gate->filterPrefixes(PrefixSetting::where('table_name', 'transaction_mapp')->get());
                $prefixes_payments = $gate->filterPrefixes(PrefixSetting::where('table_name', 'transaction_payments')->get());
            } catch (\Throwable) {
                $prefixes = collect();
                $prefixes_mapp = collect();
                $prefixes_payments = collect();
            }
        }

        $settings = Setting::getNotesAndTermsConditions();
        $inventory_costing_method = $gate->settingAllowed('inventory_costing')
            ? Setting::getInventoryCostingMethod()
            : null;
        $currencies = Country::all();
        $setting_currency = Setting::getCurrency();

        // Chart of accounts is ledger infrastructure (not sellable UI). Load for
        // payment methods / invoice settings / any entitled commercial poster.
        $accounts = collect();
        if (
            $gate->allows('accounting')
            || $gate->settingAllowed('payment_methods')
            || $gate->settingAllowed('invoice_settings')
            || $gate->allows(['sales', 'purchases', 'expenses', 'cashier_pos', 'inventory'])
        ) {
            try {
                $accounts = AccountingAccount::all();
            } catch (\Throwable) {
                $accounts = collect();
            }
        }

        $enabledModules = json_decode(Setting::where('key', 'enabled_modules')->value('value'), true) ?? [];
        $reward_points_settings = $gate->settingAllowed('reward_points')
            ? (json_decode(Setting::where('key', 'reward_points_settings')->value('value'), true) ?? [])
            : [];
        $modules = [
            'categories' => 'categories',
            'inventory' => 'inventory',
            'sales' => 'sales',
            'purchases' => 'purchases',
            'accounting' => 'accounting',
            'accounting_reports' => 'accounting_reports',
            'facilities' => 'facilities',
            'clients' => 'clients',
            'suppliers' => 'suppliers',
            'employees' => 'employees',
            'screens' => 'screens',
            'reports' => 'reports',
        ];

        $company = FacadesDB::connection('mysql')
            ->table('companies')
            ->join('users', 'companies.user_id', '=', 'users.id')
            ->select('companies.*', 'users.email')
            ->where('companies.id', get_company_id())
            ->first();
        $countries = FacadesDB::connection('mysql')->table('countries')->get(['id', 'name_en', 'name_ar']);
        $countries = $countries->map(function ($country) {
            return [
                'id' => $country->id,
                'name' => session('locale') == 'ar' ? $country->name_ar : $country->name_en,
            ];
        });

        $policy = 'perpetual';
        if ($gate->settingAllowed('inventory_policy')) {
            try {
                $policy = Setting::getInventoryTrackingPolicy();
            } catch (\Throwable) {
                $policy = 'perpetual';
            }
        }
        $inventoryCountFrequency = Setting::where('key', 'inventory_count_frequency')->value('value') ?? 'monthly';
        $unit = null;
        if ($gate->settingAllowed('default_unit')) {
            try {
                $unit = UnitTransfer::where('default', 1)->first();
            } catch (\Throwable) {
                $unit = null;
            }
        }

        $social_keys = ['social_whatsapp', 'social_facebook', 'social_instagram', 'social_snapchat', 'social_x', 'menu_cover_image'];
        $social_settings = Setting::whereIn('key', $social_keys)->get();
        $settings = $settings->merge($social_settings);

        $subscriptionModuleLabels = $this->subscriptionModuleLabels();
        $hasPrefixSettings = $prefixes->isNotEmpty() || $prefixes_mapp->isNotEmpty() || $prefixes_payments->isNotEmpty();

        return view('general::settings.index', compact(
            'cards',
            'accounts',
            'reward_points_settings',
            'modules',
            'company',
            'countries',
            'enabledModules',
            'currencies',
            'setting_currency',
            'inventory_costing_method',
            'settings',
            'prefixes',
            'prefixes_mapp',
            'prefixes_payments',
            'hasPrefixSettings',
            'taxes',
            'taxesColumns',
            'methodColumns',
            'employees',
            'notifications_settings',
            'notifications_settings_parameters',
            'policy',
            'inventoryCountFrequency',
            'unit',
            'subscriptionModuleLabels'
        ));
    }

    public function subscription()
    {
        $overview = app(\App\Services\SubscriptionOverviewService::class)->forCompany();

        return view('general::subscription.index', $overview);
    }

    public function manageSubscription()
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $companyId = get_company_id();
        if (! $companyId) {
            return redirect()
                ->route('subscription')
                ->with('error', __('general::general.subscription_manage_unavailable'));
        }

        $url = app(\App\Services\CentralSubscribeHandoff::class)->createUrl(
            userId: (int) $user->id,
            companyId: (int) $companyId,
            redirectTo: '/subscribe',
        );

        return redirect()->away($url);
    }

    public function updateModules(Request $request)
    {
        $gate = app(\App\Services\EntitlementGate::class);
        $state = $gate->forCompany();

        if (! $state['legacy']) {
            return redirect()->back()->with('error', __('general::general.subscription_modules_locked'));
        }

        try {
            $enabledModules = $request->input('modules', []);

            Setting::updateOrCreate(
                ['key' => 'enabled_modules'],
                ['value' => json_encode($enabledModules)]
            );

            return redirect()->back()->with('success', __('messages.add_successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('messages.something_went_wrong'));
        }
    }

    /**
     * @return array<string, string>
     */
    protected function subscriptionModuleLabels(): array
    {
        $localeIsAr = app()->getLocale() === 'ar' || session('locale') === 'ar';
        $labels = [
            'platform' => $localeIsAr ? 'المنصة الأساسية' : 'Core platform',
        ];

        try {
            if (! \Illuminate\Support\Facades\Schema::connection('mysql')->hasTable('entitlement_products')) {
                return $labels;
            }

            $rows = FacadesDB::connection('mysql')
                ->table('entitlement_products')
                ->get(['key', 'name_en', 'name_ar']);

            foreach ($rows as $row) {
                $labels[$row->key] = $localeIsAr
                    ? ((string) ($row->name_ar ?: $row->name_en))
                    : ((string) ($row->name_en ?: $row->name_ar));
            }
        } catch (\Throwable) {
            // keep fallbacks
        }

        return $labels;
    }

    public function updateRewardPoints(Request $request)
    {
        $this->abortUnlessSettingEntitled('reward_points');

        $data = $request->except('_token');

        Setting::updateOrCreate(
            ['key' => 'reward_points_settings'],
            ['value' => json_encode($data)]
        );

        return back()->with('success', __('messages.updated_successfully'));
    }

    public function updatePrefix(Request $request)
    {
        $gate = app(\App\Services\EntitlementGate::class);

        $prefixes = $request->input('prefixes', []) ?: [];
        $prefixes_payments = $request->input('prefixes_payments', []) ?: [];
        $prefixes_mapp = $request->input('prefixes_mapp', []) ?: [];

        foreach ($prefixes as $type => $prefix) {
            if (! $gate->prefixTypeAllowed((string) $type)) {
                continue;
            }
            PrefixSetting::updateOrCreate(
                ['type' => $type],
                ['prefix' => $prefix]
            );
        }

        foreach ($prefixes_payments as $type => $prefix) {
            if (! $gate->prefixTypeAllowed((string) $type)) {
                continue;
            }
            PrefixSetting::updateOrCreate(
                ['type' => $type],
                ['prefix' => $prefix]
            );
        }
        foreach ($prefixes_mapp as $type => $prefix) {
            if (! $gate->prefixTypeAllowed((string) $type)) {
                continue;
            }
            PrefixSetting::updateOrCreate(
                ['type' => $type],
                ['prefix' => $prefix]
            );
        }

        PrefixSetting::updateRefNumbers();

        return redirect()->back()->with('success', __('product::messages.add_successfully'));
    }

    public function saveNotsTerms(Request $request)
    {
        $this->abortUnlessSettingEntitled('invoice_settings');

        try {
            $settings = [
                ['key' => 'terms_and_conditions_en', 'value' => $request->input('terms_and_conditions_en')],
                ['key' => 'terms_and_conditions_ar', 'value' => $request->input('terms_and_conditions_ar')],
                ['key' => 'note_ar', 'value' => $request->input('note_ar')],
                ['key' => 'note_en', 'value' => $request->input('note_en')],
            ];

            FacadesDB::beginTransaction();
            foreach ($settings as $setting) {
                Setting::updateOrCreate(
                    ['key' => $setting['key']],
                    ['value' => $setting['value']]
                );
            }

            FacadesDB::commit();

            return redirect()->back()->with('success', __('messages.add_successfully'));
        } catch (Exception $e) {
            FacadesDB::rollBack();

            return redirect()->back()->with('error', __('messages.something_went_wrong'));
        }
    }

    public function updateInventoryCostingMethod(Request $request)
    {
        $this->abortUnlessSettingEntitled('inventory_costing');

        try {
            $settings = [
                ['key' => 'inventory_costing_method', 'value' => $request->input('inventory_costing_method')],
            ];

            FacadesDB::beginTransaction();
            foreach ($settings as $setting) {
                Setting::updateOrCreate(
                    ['key' => $setting['key']],
                    ['value' => $setting['value']]
                );
            }

            FacadesDB::commit();

            return redirect()->back()->with('success', __('messages.add_successfully'));
        } catch (Exception $e) {
            FacadesDB::rollBack();

            return redirect()->back()->with('error', __('messages.something_went_wrong'));
        }
    }

    public function previewInventoryCostingRebuild()
    {
        $this->abortUnlessInventoryCostingDebugTool();

        try {
            $preview = app(\Modules\Inventory\Services\InventoryCostingService::class)->previewRebuild();

            return response()->json([
                'success' => true,
                'data' => $preview,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function rebuildInventoryCosting(Request $request)
    {
        $this->abortUnlessInventoryCostingDebugTool();

        $request->validate([
            'confirm_rebuild' => 'required|accepted',
            'confirm_rebuild_final' => 'required|accepted',
            'preview_token' => 'required|string',
        ]);

        try {
            $stats = app(\Modules\Inventory\Services\InventoryCostingService::class)
                ->rebuildFromHistory($request->input('preview_token'));

            return redirect()->back()->with('success', __('general::general.inventory_costing_rebuild_success', [
                'movements' => $stats['movements'],
                'products' => $stats['products'],
                'transactions' => $stats['transactions'],
                'method' => $stats['method'] ?? '',
            ]));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function updateCurrency(Request $request)
    {
        try {
            $settings = [
                ['key' => 'currency', 'value' => $request->input('currency')],
            ];

            FacadesDB::beginTransaction();
            foreach ($settings as $setting) {
                Setting::updateOrCreate(
                    ['key' => $setting['key']],
                    ['value' => $setting['value']]
                );
            }

            FacadesDB::commit();

            return redirect()->back()->with('success', __('messages.add_successfully'));
        } catch (Exception $e) {
            FacadesDB::rollBack();

            return redirect()->back()->with('error', __('messages.something_went_wrong'));
        }
    }

    public function getInvoiceSettings()
    {
        $this->abortUnlessSettingEntitled('invoice_settings');

        $toggleCouponSetting = Setting::where('key', 'toggleCoupon')->value('value');
        $toggleSellWithModifiersCombos = Setting::where('key', 'toggleSellWithModifiersCombos')->value('value');

        return response()->json([
            'success' => true,
            'data' => [
                'cost_center' => Setting::where('key', 'toggleCost_center')->value('value') == 1,
                'storehouse' => Setting::where('key', 'toggleStorehouse')->value('value') == 1,
                'delegates' => Setting::where('key', 'toggleDelegates')->value('value') == 1,
                // Default is enabled when setting is not created yet.
                'coupon' => is_null($toggleCouponSetting) ? true : ((int) $toggleCouponSetting === 1),
                // Default is disabled when setting is not created yet.
                'sell_with_modifiers_combos' => (int) $toggleSellWithModifiersCombos === 1,
            ],
        ]);
    }

    public function updateInvoiceSetting(Request $request)
    {
        $this->abortUnlessSettingEntitled('invoice_settings');

        $request->validate([
            'key' => 'required|string',
            'value' => 'required|boolean',
        ]);

        Setting::updateOrCreate(
            ['key' => $request->key],
            ['value' => $request->value]
        );

        return response()->json(['success' => true]);
    }

    public function updateInventorySettings(Request $request)
    {
        $this->abortUnlessSettingEntitled('inventory_policy');

        try {
            $request->validate([
                'inventory_tracking_policy' => 'required|in:periodic,perpetual',
            ]);

            $trackingPolicy = $request->input('inventory_tracking_policy', 'perpetual');

            Setting::updateOrCreate(
                ['key' => 'inventory_tracking_policy'],
                ['value' => $trackingPolicy]
            );

            return redirect()->back()->with('success', __('messages.add_successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('messages.something_went_wrong'));
        }
    }

    public function updateUnit(Request $request)
    {
        $unit = UnitTransfer::where('id', $request->unit_transfer_id)->first();
        $unit->unit1 = $request->unit1;
        $unit->save();

        return redirect()->back()->with('success', __('messages.add_successfully'));
    }

    private function abortUnlessInventoryCostingDebugTool(): void
    {
        if (! config('app.debug')) {
            abort(404);
        }

        $this->abortUnlessSettingEntitled('inventory_costing');
    }

    private function abortUnlessSettingEntitled(string $section): void
    {
        if (! tenant_setting_entitled($section)) {
            abort(403, __('responses.entitlement_forbidden'));
        }
    }
}
