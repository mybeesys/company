<?php

namespace Modules\General\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use DB;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\General\Models\NotificationSetting;
use Modules\General\Models\NotificationSettingParameter;
use Modules\General\Models\PaymentMethod;
use Modules\General\Models\Tax;
use Modules\General\Transformers\CompanyResource;

class GeneralController extends Controller
{
    public function companyDetails()
    {
        return new CompanyResource(Company::find(get_company_id()));
    }

}