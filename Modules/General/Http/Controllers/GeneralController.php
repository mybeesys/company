<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB as FacadesDB;
use Modules\Employee\Models\Employee;
use Modules\General\Models\Country;
use Modules\General\Models\NotificationSetting;
use Modules\General\Models\NotificationSettingParameter;
use Modules\General\Models\PaymentMethod;
use Modules\General\Models\PrefixSetting;
use Modules\General\Models\Setting;
use Modules\General\Models\Tax;
use Predis\Configuration\Option\Prefix;

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
        $state = $request->input('state') === "true" ? true : false;

        session(['sidebar_minimize' => $state]);

        return response()->json(['success' => true]);
    }


    public function setting(Request $request)
    {
        $cards = [
            [
                'name' => __('menuItemLang.taxes'),
                'route' => 'taxes',
                'icon' => 'ki-outline fas fa-percent',
            ],
            [
                'name' => __('general::lang.payment_methods'),
                'route' => 'payment-methods',
                'icon' => 'fa-solid fa-wallet',
            ],
        ];

        $taxesColumns = Tax::getsTaxesColumns();
        $taxes = Tax::where('is_tax_group', 0)->get();
        $methodColumns = PaymentMethod::getsPaymentMethodsColumns();
        $employees = Employee::where('pos_is_active', true)->select('name', 'name_en', 'id')->get();
        $notifications_settings = NotificationSetting::all();
        $notifications_settings_parameters = NotificationSettingParameter::all();
        $prefixes = PrefixSetting::where('table_name', 'transactions')->get();
        $prefixes_mapp = PrefixSetting::where('table_name', 'transaction_mapp')->get();
        $prefixes_payments = PrefixSetting::where('table_name', 'transaction_payments')->get();
        $settings = Setting::getNotesAndTermsConditions();
        $inventory_costing_method = Setting::getInventoryCostingMethod();
        $currencies = Country::all();
        $setting_currency = Setting::getCurrency();

        $enabledModules = json_decode(Setting::where('key', 'enabled_modules')->value('value'), true) ?? [];

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


        if (!get_company_id()) {
            return redirect()->back()->with('error', __('establishment::responses.no_company_found'));
        }
        // $company = FacadesDB::connection('mysql')->table('companies')->find(get_company_id());
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

        $policy = Setting::where('key', 'inventory_tracking_policy')->value('value') ?? 'perpetual';
        $allowSaleWithoutStock = Setting::where('key', 'allow_sale_without_stock')->value('value');
        // dd($allowSaleWithoutStock);
        $inventoryCountFrequency = Setting::where('key', 'inventory_count_frequency')->value('value') ?? 'monthly';

        return view('general::settings.index', compact('cards', 'modules', 'company', 'countries', 'enabledModules', 'currencies', 'setting_currency', 'inventory_costing_method', 'settings', 'prefixes', 'prefixes_mapp', 'prefixes_payments', 'taxes', 'taxesColumns', 'methodColumns', 'employees', 'notifications_settings', 'notifications_settings_parameters', 'policy', 'allowSaleWithoutStock', 'inventoryCountFrequency'));
    }

    public function subscription()
    {
        $company = Company::findOrFail(get_company_id());
        $current_subscription = $company->subscription;
        $old_subscriptions = $company->subscription->withoutGlobalScopes()->whereNot('id', $current_subscription->id)->get();
        $user = FacadesDB::connection('mysql')->table('users')->where('id', $company->user_id)->get(['id', 'email', 'name'])->first();
        return view('general::subscription.index', compact('company', 'current_subscription', 'old_subscriptions', 'user'));
    }


    public function updateModules(Request $request)
    {
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

    public function updatePrefix(Request $request)
    {

        $prefixes = $request->input('prefixes');
        $prefixes_payments = $request->input('prefixes_payments');
        $prefixes_mapp = $request->input('prefixes_mapp');

        foreach ($prefixes as $type => $prefix) {
            PrefixSetting::updateOrCreate(
                ['type' => $type],
                ['prefix' => $prefix]
            );
        }


        foreach ($prefixes_payments as $type => $prefix) {
            PrefixSetting::updateOrCreate(
                ['type' => $type],
                ['prefix' => $prefix]
            );
        }
        foreach ($prefixes_mapp as $type => $prefix) {
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
        return response()->json([
            'success' => true,
            'data' => [
                'cost_center' => Setting::where('key', 'toggleCost_center')->value('value') == 1,
                'storehouse' => Setting::where('key', 'toggleStorehouse')->value('value') == 1,
                'delegates' => Setting::where('key', 'toggleDelegates')->value('value') == 1
            ]
        ]);
    }

    public function updateInvoiceSetting(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required|boolean'
        ]);

        Setting::updateOrCreate(
            ['key' => $request->key],
            ['value' => $request->value]
        );

        return response()->json(['success' => true]);
    }

    public function updateInventorySettings(Request $request)
    {
        // return $request;
        try {
            $trackingPolicy = $request->input('inventory_tracking_policy');
            $allowSaleWithoutStock = $request->input('allow_sale_without_stock', false);
            // return  $allowSaleWithoutStock ? 'true' : 'false';
            $inventoryCountFrequency = $request->input('inventory_count_frequency');

            Setting::updateOrCreate(
                ['key' => 'inventory_tracking_policy'],
                ['value' => $trackingPolicy]
            );

            if ($trackingPolicy === 'perpetual') {
                Setting::updateOrCreate(
                    ['key' => 'allow_sale_without_stock'],
                    ['value' => $allowSaleWithoutStock ? 'true' : 'false']
                );

                Setting::where('key', 'inventory_count_frequency')->delete();
            } elseif ($trackingPolicy === 'periodic') {
                Setting::updateOrCreate(
                    ['key' => 'inventory_count_frequency'],
                    ['value' => $inventoryCountFrequency]
                );

                Setting::where('key', 'allow_sale_without_stock')->delete();
            }

            return redirect()->back()->with('success', __('messages.add_successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('messages.something_went_wrong'));
        }
    }
}
