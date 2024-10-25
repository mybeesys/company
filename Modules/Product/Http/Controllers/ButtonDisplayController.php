<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Product\Enums\ButtonDisplay;
use Illuminate\Http\JsonResponse;

class ButtonDisplayController extends Controller
{
    public function getButtonDisplayValues(): JsonResponse
    {
        return response()->json(ButtonDisplay::all());
    }
}

?>