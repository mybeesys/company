<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Enums\ServiceFeeAutoApplyType;

class ServiceFeeAutoApplyTypeController extends Controller
{
    public function getServiceFeeAutoApplyValues(): JsonResponse
    {
        return response()->json(ServiceFeeAutoApplyType::all());
    }
}
