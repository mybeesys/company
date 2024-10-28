<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Product\Enums\Mode;
use Illuminate\Http\JsonResponse;
use Modules\Product\Enums\ServiceFeeApplicationType;
use Modules\Product\Enums\ServiceFeeAutoApplyType;
use Modules\Product\Enums\ServiceFeeCalculationMethod;
use Modules\Product\Enums\ServiceFeeType;

class ServiceFeeAutoApplyTypeController extends Controller
{
    public function getServiceFeeAutoApplyValues(): JsonResponse
    {
        return response()->json(ServiceFeeAutoApplyType::all());
    }
}

?>