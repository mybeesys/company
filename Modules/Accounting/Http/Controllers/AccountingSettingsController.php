<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccountingSettingsController extends Controller
{
    /**
     * Accounting settings hub (financial year UI + accounts routing tab).
     */
    public function index(Request $request)
    {
        $activeTab = $request->query('tab') === 'accounts-routing'
            ? 'accounts-routing'
            : 'financial-year';

        $routing = AccountsRoutingController::routingSettingsData();

        if ($activeTab === 'accounts-routing' && ! $routing['hasAccounts']) {
            return redirect()->route('tree-of-accounts')->with('error', __('accounting::lang.no_accounts'));
        }

        return view('accounting::settings.index', array_merge(
            ['activeTab' => $activeTab],
            $routing
        ));
    }
}
