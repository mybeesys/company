<?php

namespace Modules\Reservation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Reservation\Support\TableRealtimePayload;

class RealtimeInternalController extends Controller
{
    public function tablesSnapshot(Request $request)
    {
        $establishmentId = $request->query('establishment_id');
        $establishmentId = $establishmentId !== null ? (int) $establishmentId : null;

        return response()->json([
            'data' => TableRealtimePayload::tablesSnapshot($establishmentId),
        ]);
    }

    public function tableOrder(int $tableId)
    {
        $details = TableRealtimePayload::formatTableDetails($tableId);
        if (! $details) {
            return response()->json(['message' => 'Table not found'], 404);
        }

        return response()->json($details);
    }
}
