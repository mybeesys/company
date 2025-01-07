<?php

namespace Modules\Product\Http\Controllers;

use App\Helpers\TaxHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Tax;

class GeneralController extends Controller
{
    public function searchEstablishments(Request $request)
    {
        $query = $request->query('query');  // Get 'query' parameter
        $key = $request->query('key', '');
        $establishments = Establishment::where(function ($query) use($key) {
            $query->where('is_main' ,'=', 0);
            $query->where('is_active' ,'=', 1);
            $query->where('name', 'like', '%' . $key . '%');
        })->take(10)->get();
        return response()->json($establishments);
    }

    public function taxes(Request $request)
    {
        $taxes = Tax::all();
        return response()->json($taxes);
    }

    public function priceWithTax(Request $request)
    {
        $tax_id = $request->query('tax_id', '');
        $price = $request->query('price', '');
        if(!isset($tax_id) || $tax_id == null || !isset($price) || $price==null)
            return response()->json(['price_with_tax' => 0]);
        $tax = Tax::find($tax_id);
        $taxValue = TaxHelper::getTax($price, $tax->amount);
        return response()->json(['price_with_tax' => $price + $taxValue]);
    }
}