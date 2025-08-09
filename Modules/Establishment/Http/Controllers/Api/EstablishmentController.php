<?php

namespace Modules\Establishment\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Establishment\Models\Establishment;
use Modules\Establishment\Models\EstPos;
use Modules\Establishment\Transformers\Collections\EstablishmentCollection;

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

    public function devices()
    {
        return $devices = EstPos::with('establishment')->get();
    }


    public function device(Request $request)
    {
        $request->validate([
            'pin' => 'required',
            'establishment_id' => 'required'
        ]);

         $device = EstPos::where('ref', $request->pin)
            ->where('establishment_id', $request->establishment_id)
            ->with('establishment')->first();


        if (!$device) {
            return response()->json(['message' => 'Device not found '], 404);
        }

        return $device;
    }
}