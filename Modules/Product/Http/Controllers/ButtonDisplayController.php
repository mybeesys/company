<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Enums\ButtonDisplay;

class ButtonDisplayController extends Controller
{
    public function getButtonDisplayValues(): JsonResponse
    {
        return response()->json(ButtonDisplay::all());
    }
}
