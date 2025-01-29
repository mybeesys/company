<?php

namespace Modules\Reservation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Reservation\Enums\TableStatusType;

class TableStatusTypeController extends Controller
{
    public function getTableStatusTypeValues(): JsonResponse
    {
        return response()->json(TableStatusType::all());
    }
}

?>