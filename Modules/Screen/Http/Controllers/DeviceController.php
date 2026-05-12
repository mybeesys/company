<?php

namespace Modules\Screen\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\Screen\Models\Device;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
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
                        $hasPin = ! empty($row->pin_hash);
                        $actions .= '
                            <a class="btn btn-icon btn-sm btn-light border border-gray-300 btn-active-light-primary w-35px h-35px device-edit-btn me-1" data-id="'.$row->id.'" data-code="'.e($row->code).'" data-establishment-id="'.$establishmentId.'" data-has-pin="'.($hasPin ? '1' : '0').'"
                                title="'.e(__('screen::general.edit')).'" aria-label="'.e(__('screen::general.edit')).'">
                                <i class="fas fa-pen fs-6 text-gray-600"></i>
                            </a>';
                        $actions .= '
                            <a class="btn btn-icon btn-sm btn-light border border-gray-300 btn-active-light-danger w-35px h-35px device-delete-btn me-1" data-id="'.$row->id.'"
                                title="'.e(__('screen::general.delete')).'" aria-label="'.e(__('screen::general.delete')).'">
                                <i class="fas fa-trash-alt fs-6 text-gray-600"></i>
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
            'pin' => ['nullable', 'string', 'min:4', 'max:32'],
        ];
        if ($hasEstablishmentColumn) {
            $rules['establishment_id'] = ['required', 'integer', 'exists:est_establishments,id'];
        }
        $validated = $request->validate($rules);

        $payload = ['code' => $validated['code']];
        if ($hasEstablishmentColumn) {
            $payload['establishment_id'] = $validated['establishment_id'];
        }
        if (! empty($validated['pin'])) {
            $payload['pin_hash'] = Hash::make($validated['pin']);
        }

        $device = Device::create($payload);

        $device->tokens()->delete();
        $pairingPlain = $device->assignNewPairingToken();
        $pairingQrSvg = (string) QrCode::format('svg')->size(240)->margin(1)->generate($pairingPlain);

        return response()->json([
            'message' => __('employee::responses.operation_success'),
            'data' => [
                'id' => $device->id,
                'name' => $device->code,
                'pairing_token' => $pairingPlain,
                'pairing_qr_svg' => $pairingQrSvg,
            ],
        ]);
    }

    public function update(Request $request, Device $device)
    {
        $hasEstablishmentColumn = Schema::hasColumn('screen_devices', 'establishment_id');
        $rules = [
            'code' => ['required', 'string', 'max:255', 'unique:screen_devices,code,'.$device->id],
            'pin' => ['nullable', 'string', 'min:4', 'max:32'],
            'clear_pin' => ['sometimes', 'boolean'],
        ];
        if ($hasEstablishmentColumn) {
            $rules['establishment_id'] = ['required', 'integer', 'exists:est_establishments,id'];
        }
        $validated = $request->validate($rules);

        $payload = ['code' => $validated['code']];
        if ($hasEstablishmentColumn) {
            $payload['establishment_id'] = $validated['establishment_id'];
        }

        if ($request->boolean('clear_pin')) {
            $payload['pin_hash'] = null;
        } elseif (! empty($validated['pin'])) {
            $payload['pin_hash'] = Hash::make($validated['pin']);
        }

        $device->update($payload);

        return response()->json(['message' => __('employee::responses.updated_successfully', ['name' => __('screen::fields.device')])]);
    }

    public function destroy(Device $device)
    {
        if ($device->playlists()->exists()) {
            return response()->json(['error' => __('screen::general.device_in_use_cannot_delete')], 422);
        }

        $device->tokens()->delete();
        $delete = $device->delete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('screen::fields.device')])]);
        }

        return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
    }

    public function byEstablishments(Request $request)
    {
        $ids = $request->input('establishments_ids', []);
        if (! is_array($ids)) {
            $ids = array_filter(explode(',', (string) $ids));
        }

        $devices = Device::query()
            ->when(Schema::hasColumn('screen_devices', 'establishment_id') && ! empty($ids), fn ($q) => $q->whereIn('establishment_id', $ids))
            ->orderBy('code')
            ->get(['id', 'code']);

        return response()->json([
            'data' => $devices->map(fn ($d) => ['id' => $d->id, 'name' => $d->code])->values(),
        ]);
    }

    /**
     * إعادة توليد رمز الاقتران (QR) وإبطال توكنات Sanctum السابقة لهذا الجهاز.
     */
    public function regenerateScreenPairing(Device $device): \Illuminate\Http\JsonResponse
    {
        $device->tokens()->delete();
        $pairingPlain = $device->assignNewPairingToken();
        $pairingQrSvg = (string) QrCode::format('svg')->size(240)->margin(1)->generate($pairingPlain);

        return response()->json([
            'message' => __('screen::general.screen_pairing_regenerated'),
            'pairing_token' => $pairingPlain,
            'pairing_qr_svg' => $pairingQrSvg,
        ]);
    }
}
