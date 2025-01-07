<?php

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang;
use Modules\Product\Enums\DiscountFunction;
use Modules\Product\Enums\DiscountQualification;
use Modules\Product\Enums\DiscountQualificationType;
use Modules\Product\Enums\DiscountType;
use Modules\Product\Models\Discount;
use Modules\Product\Models\Transformers\Collections\DiscountCollection;

class DiscountController extends Controller
{
    public function discountFunctions()
    {
        $result = DiscountFunction::all();
        return response()->json([
            'data' => array_map(function($item) {
                $newItem["id"] = $item["value"];
                $newItem["name_ar"] = Lang::get('product::messages.discount_'.$item["name"], [], 'ar'); ;
                $newItem["name_en"] = Lang::get('product::messages.discount_'.$item["name"], [], 'en'); ;
                return $newItem;
            }, $result)
        ]);
    }

    public function discountTypes()
    {
        $result = DiscountType::all();
        return response()->json([
            'data' => array_map(function($item) {
                $newItem["id"] = $item["value"];
                $newItem["name_ar"] = Lang::get('product::messages.discount_'.$item["name"], [], 'ar'); ;
                $newItem["name_en"] = Lang::get('product::messages.discount_'.$item["name"], [], 'en'); ;
                return $newItem;
            }, $result)
        ]);
    }

    public function discountQualifications()
    {
        $result = DiscountQualification::all();
        return response()->json([
            'data' => array_map(function($item) {
                $newItem["id"] = $item["value"];
                $newItem["name_ar"] = Lang::get('product::messages.discount_qualification_'.$item["name"], [], 'ar'); ;
                $newItem["name_en"] = Lang::get('product::messages.discount_qualification_'.$item["name"], [], 'en'); ;
                return $newItem;
            }, $result)
        ]);
    }

    public function discountQualificationTypes()
    {
        $result = DiscountQualificationType::all();
        return response()->json([
            'data' => array_map(function($item) {
                $newItem["id"] = $item["value"];
                $newItem["name_ar"] = Lang::get('product::messages.discount_qualification_type_'.$item["name"], [], 'ar'); ;
                $newItem["name_en"] = Lang::get('product::messages.discount_qualification_type_'.$item["name"], [], 'en'); ;
                return $newItem;
            }, $result)
        ]);
    }

    public function discounts()
    {
        $result = Discount::with('items')->with(['dates' => function ($query) {
            $query->with(['times' => function ($query) {
                $query->where('active', 1);
            }]);
        }])->get();
        return new DiscountCollection($result);
    }
}