<?php

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Enums\ServiceFeeType;
use Modules\Product\Models\ServiceFee;
use Illuminate\Support\Facades\Lang;
use Modules\Product\Enums\ServiceFeeApplicationType;
use Modules\Product\Enums\ServiceFeeAutoApplyType;
use Modules\Product\Enums\ServiceFeeCalculationMethod;
use Modules\Product\Models\Transformers\Collections\ServiceFeeCollection;

class ServiceFeeController extends Controller
{
    public function serviceFeeTypes()
    {
        $result = ServiceFeeType::all();
        return response()->json([
            'data' => array_map(function($item) {
                $newItem["id"] = $item["value"];    
                $newItem["name_ar"] = Lang::get('product::messages.service_fee_type_'.$item["name"], [], 'ar'); ;
                $newItem["name_en"] = Lang::get('product::messages.service_fee_type_'.$item["name"], [], 'en'); ;
                return $newItem;
            }, $result)
        ]);
    }

    public function serviceFeeApplicationTypes()
    {
        $result = ServiceFeeApplicationType::all();
        return response()->json([
            'data' => array_map(function($item) {
                $newItem["id"] = $item["value"];
                $newItem["name_ar"] = Lang::get('product::messages.service_fee_app_type_'.$item["name"], [], 'ar'); ;
                $newItem["name_en"] = Lang::get('product::messages.service_fee_app_type_'.$item["name"], [], 'en'); ;
                return $newItem;
            }, $result)
        ]);
    }
    
    public function serviceFeeCalculationMethods()
    {
        $result = ServiceFeeCalculationMethod::all();
        return response()->json([
            'data' => array_map(function($item) {
                $newItem["id"] = $item["value"];
                $newItem["name_ar"] = Lang::get('product::messages.service_fee_calc_method_'.$item["name"], [], 'ar'); ;
                $newItem["name_en"] = Lang::get('product::messages.service_fee_calc_method_'.$item["name"], [], 'en'); ;
                return $newItem;
            }, $result)
        ]);
    }

    public function serviceFeeAutoApplyTypes()
    {
        $result = ServiceFeeAutoApplyType::all();
        return response()->json([
            'data' => array_map(function($item) {
                $newItem["id"] = $item["value"];
                $newItem["name_ar"] = Lang::get('product::messages.'.$item["name"], [], 'ar'); ;
                $newItem["name_en"] = Lang::get('product::messages.'.$item["name"], [], 'en'); ;
                return $newItem;
            }, $result)
        ]);
    }

    public function serviceFees(Request $request)
    {
        $serviceFees = ServiceFee::where('active', '=', 1)
                                ->with(['diningTypes' => function ($query) {
                                    $query->with('diningType');
                                }])
                                ->with(['cards' => function ($query) {
                                    $query->with('paymentCard');
                                }])->get();
        return new ServiceFeeCollection($serviceFees);
    }
}