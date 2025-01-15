<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\General\Models\NotificationSetting;
use Modules\General\Models\NotificationSettingParameter;
use Modules\General\Models\PaymentMethod;
use Modules\General\Models\Tax;

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
        return view('general::settings.index', compact('cards', 'taxes', 'taxesColumns', 'methodColumns', 'employees', 'notifications_settings', 'notifications_settings_parameters'));
    }
}