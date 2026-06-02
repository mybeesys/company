<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\General\Models\Setting;

class AccountingSettingsController extends Controller
{
    /**
     * Accounting settings hub (financial year UI + accounts routing tab).
     */
    public function index(Request $request)
    {
        // Default to accounts routing tab (ERP setup-first flow).
        $activeTab = $request->query('tab') === 'financial-year'
            ? 'financial-year'
            : 'accounts-routing';

        $routing = AccountsRoutingController::routingSettingsData();

        if ($activeTab === 'accounts-routing' && ! $routing['hasAccounts']) {
            return redirect()->route('tree-of-accounts')->with('error', __('accounting::lang.no_accounts'));
        }

        return view('accounting::settings.index', array_merge(
            [
                'activeTab' => $activeTab,
                'financialPeriodLockingEnabled' => Setting::isFinancialPeriodLockingEnabled(),
            ],
            $routing
        ));
    }
}
