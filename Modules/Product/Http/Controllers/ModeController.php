<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Enums\Mode;

class ModeController extends Controller
{
    public function getModeValues(): JsonResponse
    {
        return response()->json(Mode::all());
    }
}
