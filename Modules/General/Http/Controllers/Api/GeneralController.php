<?php

namespace Modules\General\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Modules\General\Transformers\CompanyResource;

class GeneralController extends Controller
{
    public function companyDetails()
    {
        return new CompanyResource(Company::find(get_company_id()));
    }
}
