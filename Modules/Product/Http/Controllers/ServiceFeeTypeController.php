<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Product\Enums\Mode;
use Illuminate\Http\JsonResponse;
use Modules\Product\Enums\ServiceFeeType;

class ServiceFeeTypeController extends Controller
{
    public function getServiceFeeTypeValues(): JsonResponse
    {
        return response()->json(ServiceFeeType::all());
    }
}

?>