<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Product\Enums\ApplicationType;
use Illuminate\Http\JsonResponse;

class ApplicationTypeController extends Controller
{
    public function getApplicationTypeValues(): JsonResponse
    {
        return response()->json(ApplicationType::all());
    }
}

?>