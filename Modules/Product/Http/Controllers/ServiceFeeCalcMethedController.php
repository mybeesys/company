<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Enums\ServiceFeeCalculationMethod;

class ServiceFeeCalcMethedController extends Controller
{
    public function getServiceFeeCalcMethodValues(): JsonResponse
    {
        return response()->json(ServiceFeeCalculationMethod::all());
    }
}
