<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Services\ContactAccountSettings;
use Modules\General\Models\Setting;

class AccountingSettingsController extends Controller
{
    /**
     * Accounting settings hub (routing + financial year + contact accounts).
     */
    public function index(Request $request)
    {
        $tab = (string) $request->query('tab', 'accounts-routing');
        $activeTab = match ($tab) {
            'financial-year' => 'financial-year',
            'contact-accounts' => 'contact-accounts',
            default => 'accounts-routing',
        };

        $routing = AccountsRoutingController::routingSettingsData();

        if ($activeTab === 'accounts-routing' && ! $routing['hasAccounts']) {
            return redirect()->route('tree-of-accounts')->with('error', __('accounting::lang.no_accounts'));
        }

        $contactAccountSettings = ContactAccountSettings::snapshot();
        $contactAccountParents = ContactAccountSettingsController::parentCandidates();

        return view('accounting::settings.index', array_merge(
            [
                'activeTab' => $activeTab,
                'financialPeriodLockingEnabled' => Setting::isFinancialPeriodLockingEnabled(),
                'contactAccountSettings' => $contactAccountSettings,
                'contactAccountParents' => $contactAccountParents,
            ],
            $routing
        ));
    }
}
