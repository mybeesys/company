<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Enums\CreditCardType;

class CreditCardTypeController extends Controller
{
    public function getCreditCardTypeValues(): JsonResponse
    {
        return response()->json(CreditCardType::all());
    }
}
