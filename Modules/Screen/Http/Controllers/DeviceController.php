<?php

namespace Modules\Screen\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\Screen\Models\Device;
use Yajra\DataTables\Facades\DataTables;

class DeviceController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $hasEstablishmentColumn = Schema::hasColumn('screen_devices', 'establishment_id');
            $devices = $hasEstablishmentColumn
                ? Device::with('establishment')->latest()->get()
                : Device::latest()->get();
            return DataTables::of($devices)
                ->addColumn(
                    'actions',
                    function ($row) {
                        $actions = '<div class="justify-content-center d-flex">';
                        $establishmentId = (int) ($row->establishment_id ?? 0);
                        $actions .= '
                            <a class="btn btn-icon btn-bg-light btn-active-color-warning w-35px h-35px device-edit-btn me-1" data-id="' . $row->id . '" data-code="' . e($row->code) . '" data-establishment-id="' . $establishmentId . '">
                                <i class="ki-outline ki-pencil fs-3"></i>
                            </a>';
                        $actions .= '
                            <a class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px device-delete-btn me-1" data-id="' . $row->id . '">
                                <i class="ki-outline ki-trash fs-3"></i>
                            </a>';

                        $actions .= '</div>';
                        return $actions;
                    }
                )->addColumn('establishment_name', function ($row) {
                    return $row->establishment?->name ?? '--';
                })->rawColumns(['actions'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $hasEstablishmentColumn = Schema::hasColumn('screen_devices', 'establishment_id');
        $rules = [
            'code' => ['required', 'string', 'max:255', 'unique:screen_devices,code'],
        ];
        if ($hasEstablishmentColumn) {
            $rules['establishment_id'] = ['required', 'integer', 'exists:est_establishments,id'];
        }
        $validated = $request->validate($rules);

        $payload = ['code' => $validated['code']];
        if ($hasEstablishmentColumn) {
            $payload['establishment_id'] = $validated['establishment_id'];
        }
        $device = Device::create($payload);
        return response()->json(['message' => __('employee::responses.operation_success'), 'data' => ['id' => $device->id, 'name' => $device->code]]);
    }

    public function update(Request $request, Device $device)
    {
        $hasEstablishmentColumn = Schema::hasColumn('screen_devices', 'establishment_id');
        $rules = [
            'code' => ['required', 'string', 'max:255', 'unique:screen_devices,code,' . $device->id],
        ];
        if ($hasEstablishmentColumn) {
            $rules['establishment_id'] = ['required', 'integer', 'exists:est_establishments,id'];
        }
        $validated = $request->validate($rules);

        $payload = ['code' => $validated['code']];
        if ($hasEstablishmentColumn) {
            $payload['establishment_id'] = $validated['establishment_id'];
        }
        $device->update($payload);

        return response()->json(['message' => __('employee::responses.updated_successfully', ['name' => __('screen::fields.device')])]);
    }

    public function destroy(Device $device)
    {
        if ($device->playlists()->exists()) {
            return response()->json(['error' => __('screen::general.device_in_use_cannot_delete')], 422);
        }

        $delete = $device->delete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('screen::fields.device')])]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }

    public function byEstablishments(Request $request)
    {
        $ids = $request->input('establishments_ids', []);
        if (!is_array($ids)) {
            $ids = array_filter(explode(',', (string) $ids));
        }

        $devices = Device::query()
            ->when(Schema::hasColumn('screen_devices', 'establishment_id') && !empty($ids), fn($q) => $q->whereIn('establishment_id', $ids))
            ->orderBy('code')
            ->get(['id', 'code']);

        return response()->json([
            'data' => $devices->map(fn($d) => ['id' => $d->id, 'name' => $d->code])->values(),
        ]);
    }
}
