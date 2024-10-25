<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Product\Enums\Mode;
use Illuminate\Http\JsonResponse;

class ModeController extends Controller
{
    public function getModeValues(): JsonResponse
    {
        return response()->json(Mode::all());
    }
}

?>