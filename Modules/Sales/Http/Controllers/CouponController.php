<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Modules\Establishment\Models\Establishment;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Sales\Classes\CouponTable;
use Modules\Sales\Http\Requests\StoreCouponRequest;
use Modules\Sales\Models\Coupon;
use Modules\Sales\Services\CouponActions;

class CouponController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $coupons = Coupon::with('products', 'categories');
            if ($request->has('deleted_records') && !empty($request->deleted_records)) {
                $request->deleted_records == 'only_deleted_records'
                    ? $coupons->onlyTrashed()
                    : ($request->deleted_records == 'with_deleted_records' ? $coupons->withTrashed() : null);
            }

            return CouponTable::getCouponTable($coupons);
        }
        $coupons_columns = CouponTable::getCouponColumns();

        $products = Product::get();
        $categories = Category::get();
        $establishments = Establishment::active()->notMain()->select('name', 'id')->get();
        return view('sales::coupon.index', compact('coupons_columns', 'products', 'categories', 'establishments'));
    }

    public function store(StoreCouponRequest $request, CouponActions $action)
    {
        $data = $request->safe();
        try {
            DB::transaction(function () use ($data, $action) {
                $action->store($data);
                return response()->json(['message' => __('employee::responses.operation_success')]);
            });
        } catch (\Throwable $e) {
            \Log::error('coupons creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }

    public function getCouponDetails($id)
    {
        $coupon = Coupon::firstWhere('id', $id);
        return response()->json(['coupon' => $coupon, 'establishments_ids' => $coupon->establishments->pluck('id'), 'products_ids' => $coupon->products->pluck('id'), 'categories_ids' => $coupon->categories->pluck('id')]);
    }

    public function destroy(Coupon $coupon)
    {
        $delete = $coupon->delete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('employee::general.this_element')])]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }

    public function restore($id)
    {
        $restore = Coupon::where('id', $id)->restore();
        if ($restore) {
            return response()->json(['message' => __('employee::responses.operation_success')]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }

    public function forceDelete($id)
    {
        $delete = Coupon::where('id', $id)->forceDelete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('employee::general.this_element')])]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}
