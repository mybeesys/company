<?php

namespace Modules\Establishment\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Establishment\Models\EstPos;
use Illuminate\Http\Request;
use Modules\Establishment\Classes\EstablishmentTable;
use Illuminate\Support\Facades\Log;
use Modules\Establishment\Models\Establishment;

class DeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $establishments = EstPos::with('establishment')->get();
        if ($request->ajax()) {
            return EstablishmentTable::getDeviceTable($establishments);
        }
        $columns = EstablishmentTable::getDeviceColumns();
        return view('establishment::device.index', compact('columns', 'establishments'));
    }

    public function getDevice()
    {
        $devices = EstPos::with('establishment')->get();
        $allWithChildren = [];

        foreach ($devices as $device) {
            $allWithChildren[] = [
                'id' => $device->id,
                'name' => $device->name,
                'type' => $device->type,
                'ref' => $device->ref,

            ];
        }

        return $allWithChildren;
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'ref' => 'required|string|max:255',
            'establishment_id' => 'required|numeric',
        ]);

        EstPos::create([
            'name' => $request->name,
            'type' => $request->type,
            'ref' => $request->ref,
            'establishment_id' => $request->establishment_id,
        ]);

        return response()->json(['success' => 'Device added successfully']);
    }
    public function getEstablishment()
    {
        $establishments = Establishment::all()->map(function ($establishment) {
            return [
                'id' => $establishment->id,
                'name' => app()->getLocale() === 'ar' ? $establishment->name : $establishment->name_en,
            ];
        });

        return response()->json($establishments);
    }
    public function destroy($id)
    {
        $device = EstPos::find($id);

        if (!$device) {
            return response()->json(['message' => 'Device not found'], 404);
        }

        $device->delete();

        return response()->json(['message' => 'Done']);
    }
}
