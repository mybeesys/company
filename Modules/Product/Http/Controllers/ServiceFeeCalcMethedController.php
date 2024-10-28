<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Product\Enums\Mode;
use Illuminate\Http\JsonResponse;
use Modules\Product\Enums\ServiceFeeApplicationType;
use Modules\Product\Enums\ServiceFeeCalculationMethod;
use Modules\Product\Enums\ServiceFeeType;

class ServiceFeeCalcMethedController extends Controller
{
    public function getServiceFeeCalcMethodValues(): JsonResponse
    {
        return response()->json(ServiceFeeCalculationMethod::all());
    }
}

?>