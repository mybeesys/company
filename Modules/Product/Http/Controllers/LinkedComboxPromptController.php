<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Enums\LinkedComboxPrompt;

class LinkedComboxPromptController extends Controller
{
    public function getLinkedComboPromptValues(): JsonResponse
    {
        return response()->json(LinkedComboxPrompt::all());
    }
}

?>