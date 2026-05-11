<?php

namespace Modules\Screen\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\Screen\Models\Device;

class ScreenDeviceApiController extends Controller
{
    public function index(): JsonResponse
    {
        $hasEstablishment = Schema::hasColumn('screen_devices', 'establishment_id');
        $query = Device::query()->orderBy('code');
        if ($hasEstablishment) {
            $query->with('establishment:id,name');
        }
        $devices = $query->get();

        return response()->json([
            'data' => $devices->map(fn (Device $d) => $this->serializeDevice($d, $hasEstablishment))->values(),
        ]);
    }

    public function show(Device $device): JsonResponse
    {
        $hasEstablishment = Schema::hasColumn('screen_devices', 'establishment_id');
        if ($hasEstablishment) {
            $device->load('establishment:id,name');
        }

        return response()->json(['data' => $this->serializeDevice($device, $hasEstablishment)]);
    }

    public function store(Request $request): JsonResponse
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

        return response()->json([
            'message' => __('employee::responses.operation_success'),
            'data' => $this->serializeDevice($device->fresh(
                $hasEstablishmentColumn ? ['establishment:id,name'] : []
            ), $hasEstablishmentColumn),
        ], 201);
    }

    public function update(Request $request, Device $device): JsonResponse
    {
        $hasEstablishmentColumn = Schema::hasColumn('screen_devices', 'establishment_id');
        $rules = [
            'code' => ['required', 'string', 'max:255', 'unique:screen_devices,code,'.$device->id],
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

        if ($hasEstablishmentColumn) {
            $device->load('establishment:id,name');
        }

        return response()->json([
            'message' => __('employee::responses.updated_successfully', ['name' => __('screen::fields.device')]),
            'data' => $this->serializeDevice($device, $hasEstablishmentColumn),
        ]);
    }

    public function destroy(Device $device): JsonResponse
    {
        if ($device->playlists()->exists()) {
            return response()->json(['message' => __('screen::general.device_in_use_cannot_delete')], 422);
        }
        $device->delete();

        return response()->json([
            'message' => __('employee::responses.deleted_successfully', ['name' => __('screen::fields.device')]),
        ]);
    }

    public function byEstablishments(Request $request): JsonResponse
    {
        $ids = $request->input('establishments_ids', []);
        if (! is_array($ids)) {
            $ids = array_filter(explode(',', (string) $ids));
        }

        $hasEstablishment = Schema::hasColumn('screen_devices', 'establishment_id');
        $columns = $hasEstablishment ? ['id', 'code', 'establishment_id'] : ['id', 'code'];

        $devices = Device::query()
            ->when($hasEstablishment && ! empty($ids), fn ($q) => $q->whereIn('establishment_id', $ids))
            ->orderBy('code')
            ->get($columns);

        return response()->json([
            'data' => $devices->map(fn ($d) => [
                'id' => $d->id,
                'code' => $d->code,
                'establishment_id' => $hasEstablishment ? ($d->establishment_id ?? null) : null,
            ])->values(),
        ]);
    }

    protected function serializeDevice(Device $device, bool $withEstablishment): array
    {
        $row = [
            'id' => $device->id,
            'code' => $device->code,
        ];
        if ($withEstablishment) {
            $row['establishment_id'] = $device->establishment_id;
            $row['establishment_name'] = $device->establishment?->name;
        }

        return $row;
    }
}
