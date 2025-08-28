<?php

namespace Modules\Accounting\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingCostCenter;

class AccountingController extends Controller
{
    public function accounts()
    {
        $accounts =  AccountingAccount::forDropdown();
        return response()->json($accounts, 200);
    }

    public function costCenters()
    {
        $cost_centers = AccountingCostCenter::forDropdown();
        return response()->json($cost_centers, 200);
    }
}
