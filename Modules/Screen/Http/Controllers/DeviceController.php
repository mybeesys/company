<?php

namespace Modules\Screen\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Screen\Models\Device;
use Yajra\DataTables\Facades\DataTables;

class DeviceController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $devices = Device::all();
            return DataTables::of($devices)
                ->addColumn(
                    'actions',
                    function ($row) {
                        $actions = '<div class="justify-content-center d-flex">';
                            $actions .= '
                            <a class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px device-delete-btn me-1" data-id="' . $row->id . '">
                                <i class="ki-outline ki-trash fs-3"></i>
                            </a>';

                        $actions .= '</div>';
                        return $actions;
                    }
                )->rawColumns(['actions'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['code' => ['required', 'string', 'unique:screen_devices,id']]);
        Device::create(['code' => $validated['code']]);
        return response()->json(['message' => __('employee::responses.operation_success')]);
    }

    
    
    public function destroy(Device $device)
    {
        $delete = $device->delete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('screen::fields.device')])]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}
