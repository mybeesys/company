<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB as FacadesDB;
use Modules\Employee\Models\Employee;
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

        return view('general::settings.index', compact('cards','inventory_costing_method', 'settings', 'prefixes', 'prefixes_mapp', 'prefixes_payments', 'taxes', 'taxesColumns', 'methodColumns', 'employees', 'notifications_settings', 'notifications_settings_parameters'));
    }

    public function subscription()
    {
        $company = Company::findOrFail(get_company_id());
        $current_subscription = $company->subscription;
        $old_subscriptions = $company->subscription->withoutGlobalScopes()->whereNot('id', $current_subscription->id)->get();
        $user = FacadesDB::connection('mysql')->table('users')->where('id', $company->user_id)->get(['id', 'email', 'name'])->first();
        return view('general::subscription.index', compact('company', 'current_subscription', 'old_subscriptions', 'user'));
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
}
