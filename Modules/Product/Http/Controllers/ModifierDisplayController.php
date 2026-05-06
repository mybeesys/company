<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Enums\ModifierDisplay;

class ModifierDisplayController extends Controller
{
    public function getModifierDisplayValues(): JsonResponse
    {
        return response()->json(ModifierDisplay::all());
    }
}
