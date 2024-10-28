<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Product\Enums\Mode;
use Illuminate\Http\JsonResponse;
use Modules\Product\Enums\CreditCardType;
use Modules\Product\Enums\ServiceFeeApplicationType;

class CreditCardTypeController extends Controller
{
    public function getCreditCardTypeValues(): JsonResponse
    {
        return response()->json(CreditCardType::all());
    }
}

?>