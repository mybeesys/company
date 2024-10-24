<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Product\Enums\ModifierDisplay;
use Illuminate\Http\JsonResponse;

class ModifierDisplayController extends Controller
{
    public function getModifierDisplayValues(): JsonResponse
    {
        return response()->json(ModifierDisplay::all());
    }
}

?>