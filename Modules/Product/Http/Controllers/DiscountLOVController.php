<?php 
namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Product\Enums\DiscountFunction;
use Modules\Product\Enums\DiscountQualification;
use Modules\Product\Enums\DiscountQualificationType;
use Modules\Product\Enums\DiscountType;

class DiscountLOVController extends Controller
{
    public function getDiscountLOVs()
    {
        $discountFunction = DiscountFunction::all();
        $discountType = DiscountType::all();
        $discountQulification = DiscountQualification::all();
        $discountQualificationType = DiscountQualificationType::all();
        $lov = [];
        $lov["discountFunction"] = $discountFunction;
        $lov["discountType"] = $discountType;
        $lov["discountQualification"] = $discountQulification;
        $lov["discountQualificationType"] = $discountQualificationType;
        return response()->json($lov);
    }

}
