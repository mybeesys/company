<?php

namespace Modules\Establishment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\EntitlementGate;
use Illuminate\Http\Request;
use Modules\Establishment\Classes\EstablishmentTable;
use Modules\Establishment\Models\Establishment;
use Modules\Establishment\Models\EstPos;

class DeviceController extends Controller
{
    public function __construct(
        protected EntitlementGate $gate,
    ) {}

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
        $deviceEntitlements = $this->gate->estPosEntitlementSnapshot();

        return view('establishment::device.index', compact('columns', 'establishments', 'deviceEntitlements'));
    }

    public function entitlementStatus()
    {
        return response()->json($this->gate->estPosEntitlementSnapshot());
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

        $type = (string) $request->type;
        $normalized = $this->gate->normalizeEstPosType($type);

        if (! isset(EntitlementGate::EST_POS_TYPE_MAP[$normalized])) {
            return response()->json([
                'message' => __('establishment::general.device_type_unsupported'),
            ], 422);
        }

        if (! $this->gate->canAddEstPosDevice($type)) {
            return response()->json([
                'message' => $this->gate->estPosDenialMessage($type),
                'entitlements' => $this->gate->estPosEntitlementSnapshot(),
            ], 422);
        }

        // Persist canonical type values for reliable quota counting.
        $storedType = match ($normalized) {
            'kitchen_screen' => 'kitchen screen',
            default => $normalized,
        };

        EstPos::create([
            'name' => $request->name,
            'type' => $storedType,
            'ref' => $request->ref,
            'establishment_id' => $request->establishment_id,
        ]);

        return response()->json([
            'success' => __('establishment::general.device_added_successfully'),
            'entitlements' => $this->gate->estPosEntitlementSnapshot(),
        ]);
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

        if (! $device) {
            return response()->json(['message' => 'Device not found'], 404);
        }

        $device->delete();

        return response()->json([
            'message' => 'Done',
            'entitlements' => $this->gate->estPosEntitlementSnapshot(),
        ]);
    }
}
