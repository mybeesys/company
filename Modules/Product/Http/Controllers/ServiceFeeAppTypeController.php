<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Enums\ServiceFeeApplicationType;

class ServiceFeeAppTypeController extends Controller
{
    public function getServiceFeeAppTypeValues(): JsonResponse
    {
        return response()->json(ServiceFeeApplicationType::all());
    }
}
