<?php

namespace Modules\ClientsAndSuppliers\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ClientsAndSuppliers\Http\Requests\StoreLoyaltyPointSetting;
use Modules\ClientsAndSuppliers\Models\ClientSupplierSetting;

class ClientSupplierSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $loyaltyPointsSettings = ClientSupplierSetting::all();
        return view('clientsandsuppliers::settings.index', compact('loyaltyPointsSettings'));
    }

    public function storeLoyaltyPointsSettings(StoreLoyaltyPointSetting $request)
    {
        foreach ($request->safe()['key'] as $index => $value) {
            ClientSupplierSetting::updateOrCreate(['key' => $index], ['value' => $value]);
        }
        return response()->json(['message' => __('employee::responses.operation_success')]);
    }
}
