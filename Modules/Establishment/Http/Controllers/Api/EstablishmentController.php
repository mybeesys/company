<?php

namespace Modules\Establishment\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Establishment\Models\Establishment;
use Modules\Establishment\Models\EstPos;
use Modules\Establishment\Transformers\Collections\EstablishmentCollection;
use Illuminate\Support\Str;

class EstablishmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $establishments = Establishment::all();
        return new EstablishmentCollection($establishments);
    }


    public function devices(Request $request)
    {
        $request->validate([
            'establishment_id' => 'required'
        ]);

        $devices = EstPos::where('establishment_id', $request->establishment_id)
            ->whereNull('token')
            ->with('establishment')
            ->get();

        if ($devices->isEmpty()) {
            return response()->json([
                'message' => 'No unassigned devices found for this establishment.',
                'devices' => []
            ], 200);
        }
        return response()->json([
            'message' => 'Devices fetched successfully',
            'devices' => $devices
        ], 200);
    }
    public function assignDeviceToken(Request $request)
    {
        $request->validate([
            'establishment_id' => 'required|integer',
            'device_id' => 'required|integer'
        ]);

        $device = EstPos::where('establishment_id', $request->establishment_id)
            ->where('id', $request->device_id)
            ->first();

        if ($device) {
            return response()->json([
                'message' => 'Device not found.',
                'device' => []
            ], 200);
        }

        $device->token = Str::random(60);
        $device->save();

        return response()->json([
            'message' => 'Devices fetched successfully',
            'device' => $device
        ], 200);
    }
}
