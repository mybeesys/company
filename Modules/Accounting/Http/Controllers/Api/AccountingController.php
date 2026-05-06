<?php

namespace Modules\Accounting\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Accounting\Transformers\AccountsResource;
use Modules\Accounting\Transformers\CostCenterResource;

class AccountingController extends Controller
{
    public function accounts()
    {
        $accounts = AccountingAccount::forDropdown();

        return response()->json(AccountsResource::collection($accounts), 200);
    }

    public function costCenters()
    {
        $cost_centers = AccountingCostCenter::forDropdown();

        return response()->json(CostCenterResource::collection($cost_centers), 200);
    }
}
