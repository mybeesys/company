<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\Vendor;

class VendorController extends Controller
{
    public function getVendorTree()
    {
        $ingredients = Vendor::all();
        $treeBuilder = new TreeBuilder();
        $tree = $treeBuilder->buildTree($ingredients ,null, 'vendor', null, null, null);
        return response()->json($tree);
    }

    public function searchVendors(Request $request)
    {
        $query = $request->query('query');  // Get 'query' parameter
        $key = $request->query('key', '');
        $vendors = Vendor::where('name_ar', 'like', '%' . $key . '%')
                            ->orWhere('name_en', 'like', '%' . $key . '%')
                            ->get();
        return response()->json($vendors);
    }

    public function vendors($id)
    {
        $vendor = Vendor::find($id);
        return response()->json($vendor);
    }

    public function index()
    {
        return view('product::vendor.index' ); 
    }

    public function edit($id)
    {
        $vendor  = Vendor::find($id);
        return view('product::vendor.edit', compact('vendor'));
    }

    public function create()
    {
        $vendor  = new Vendor();
    
        return view('product::vendor.create', compact('vendor'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'id' => 'nullable|numeric',
            'method' => 'nullable|string'
        ]);

        if (isset($validated['method']) && ($validated['method'] == "delete")) {
            $serviceFee = Vendor::find($validated['id']);
            $serviceFee->delete();
            return response()->json(["message" => "Done"]);
        }

        if (!isset($validated['id'])) {
            $vendor = Vendor::where('name_ar', $validated['name_ar'])->first();
            if($vendor != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $vendor = Vendor::where('name_en', $validated['name_en'])->first();
            if($vendor != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);
            
            Vendor::create($validated);
            
        } else {
            $vendor = Vendor::where([
                ['id', '!=', $validated['id']],
                ['name_ar', '=', $validated['name_ar']]])->first();
            if($vendor != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $vendor = Vendor::where([
                ['id', '!=', $validated['id']],
                ['name_en', '=', $validated['name_en']]])->first();
            if($vendor != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);

            $vendor = Vendor::find($validated['id']);
            $vendor->name_ar = $validated['name_ar'];
            $vendor->name_en = $validated['name_en'];
            $vendor->save();
        }
        return response()->json(["message" => "Done"]);
    }

}