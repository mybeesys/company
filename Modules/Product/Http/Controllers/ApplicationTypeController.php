<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Enums\ApplicationType;

class ApplicationTypeController extends Controller
{
    public function getApplicationTypeValues(): JsonResponse
    {
        return response()->json(ApplicationType::all());
    }
}
