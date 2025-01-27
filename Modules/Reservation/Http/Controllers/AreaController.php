<?php

namespace Modules\Reservation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Establishment\Models\Establishment;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\TreeData;
use Modules\Product\Models\TreeObject;
use Modules\Reservation\Models\Area;
use Modules\Reservation\Models\Table;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('reservation::area.index' ); 
    }

    public function areaQR()
    {
        return view('reservation::area.areaQR' ); 
    }

    protected function fillArea($establishment){
        if($establishment["is_main"] == 1){
            $children =[];
            foreach ($establishment["children"] as $childEstablishment) {
                $childEstablishment['name_ar'] = $childEstablishment['name'];
                $childEstablishment['type'] = 'establishment';
                $childEstablishment['childType'] = 'area';
                $childEstablishment['childKey'] = 'establishment_id';
                $est = $this->fillArea($childEstablishment);
                $children [] = $est;
            }
            $establishment["children"] = $children;
            return $establishment;
        }
        $areas = Area::with('children')->where('establishment_id', $establishment["id"])->get();
        $children =[];
        foreach ($areas as $area) {
            $ar = $area->toArray();
            $ar['type'] = 'area';
            $ar['childType'] = 'table';
            $ar['childKey'] = 'area_id';
            $ar['parentKey'] = 'establishment_id';
            $tables = [];
            foreach ($ar['children'] as $tt) {
                $tt['type'] = 'table';
                $tt['parentKey'] = 'area_id';
                $tables [] = $tt;
            }
            $ar['children'] = $tables;
            $children[] = $ar;
        }
        $establishment["children"] = $children;
        return $establishment;
    }

    public function getAreas()
    {
        $TreeBuilder = new TreeBuilder();
        $establishments = Establishment::whereNull('parent_id')->with('children')->get();
        $establishmentArray = $establishments->toArray();
        $details = [];
        foreach ($establishmentArray as $establishment) {
            $establishment['name_ar'] = $establishment['name'];
            $establishment['type'] = 'establishment';
            $establishment['childType'] = 'area';
            $establishment['childKey'] = 'establishment_id';
            $est = $this->fillArea($establishment);
            $details [] = $est;
        }
        $tree = $TreeBuilder->buildTreeFromArray($details ,null, 'establishment', null, null, null);
        return response()->json($tree);
    }

    public function getMiniAreas()
    {
        $TreeBuilder = new TreeBuilder();
        $result = Area::with('establishment')->orderBy('establishment_id')->get();
        foreach($result as $area){
            $area->addToFillable(['establishment']);
        }
        $tree = $TreeBuilder->buildTree($result, null, 'area', null, null, null);
        return response()->json($tree);
    }

    public function getMiniModifierClasslist()
    {
       $result = Area::all();
       return response()->json($result);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|numeric',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'establishment_id' => 'required|numeric',
            'active' => 'nullable|boolean',
            'method' => 'nullable|string'
        ]);
        if(isset($validated['method']) && ($validated['method'] =="delete"))
        {
            $table = Table::where([['area_id', '=', $validated['id']]])->first();
            if($table != null)
                return response()->json(["message"=>"CHILD_EXIST"]);
            
            $area = Area::find($validated['id']); 
            $area->delete();
            
        }
        else if(!isset($validated['id']))
        {
            $area = Area::where([['establishment_id', '=', $validated['establishment_id']],
                                    ['name_ar', '=', $validated['name_ar']]])->first();
            if($area != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $area = Area::where([['establishment_id', '=', $validated['establishment_id']],
                                ['name_en', '=', $validated['name_en']]])->first();
            if($area != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);

            Area::create($validated);
        }
        else
        {
            $area = Area::where([['establishment_id', '=', $validated['establishment_id']],
                                ['name_ar', '=', $validated['name_ar']]])->where('id', '!=', $validated['id'])->first();
            if($area != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $area = Area::where([['establishment_id', '=', $validated['establishment_id']],
                                ['name_en', '=', $validated['name_en']]])->where('id', '!=', $validated['id'])->first();
            if($area != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);

            $area = Area::find($validated['id']);
            $area->name_ar  = $validated['name_ar'];
            $area->name_en  = $validated['name_en'];
            $area->active   = $validated['active'];
            $area->save();
        }
        return response()->json(["message"=>"Done"]);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $item = Area::find($id);

        if ($item) {
            return response()->json($item);
        }

        return response()->json(['error' => 'Item not found'], 404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
         $area  = Area::find($id);
         return view('reservation::area.edit', compact('area'));
    }
}
