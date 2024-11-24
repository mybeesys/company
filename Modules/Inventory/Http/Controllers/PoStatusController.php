<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Product\Enums\Mode;
use Illuminate\Http\JsonResponse;
use Modules\Inventory\Enums\PurchaseOrderStatus;

class PoStatusController extends Controller
{
    public function getPurchaseOrderStatusValues(): JsonResponse
    {
        return response()->json(PurchaseOrderStatus::all());
    }
}

?>