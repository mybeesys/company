<?php

namespace Modules\Reservation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\TreeBuilder;
use Modules\Reservation\Models\Table;

class TableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('reservation::table.index' ); 
    }

    public function getTables()
    {
        $TreeBuilder = new TreeBuilder();
        $tables = Table::with(['area' => function ($query) {
            $query->with('establishment');
        }])->get();
        $details = [];
        foreach ($tables as $table) {
            $t = $table->toArray();
            $t["type"] = "table";
            $t["area"]['name_ar'] = $table->area->establishment->name.' - '.$table->area->name_ar;
            $t["area"]['name_en'] = $table->area->establishment->name_en.' - '.$table->area->name_en;
            $details [] = $t;
        }
        $tree = $TreeBuilder->buildTreeFromArray($details ,null, 'establishment', null, null, null);
        return response()->json($tree);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|numeric',
            'code' => 'required|string',
            'steating_capacity'=> 'required|numeric',
            'table_status'=> 'required|numeric',
            'active' => 'nullable|boolean',
            'method' => 'nullable|string'
        ]);

        if(isset($validated['method']) && ($validated['method'] =="delete"))
        {
            $table = Table::find($validated['id']); 
            $table->delete();
            return response()->json(["message"=>"Done"]);
        }
        else if(!isset($validated['id']))
        {
            $validated['area_id'] = $request['area']['id'];
            $table = Table::where([['area_id', '=', $validated['area_id']],
                                        ['code', '=', $validated['code']]])->first();
            if($table != null)
                return response()->json(["message"=>"CODE_EXIST"]);
            $this->createTable($validated, $request);   
        }
        else
        {
            $validated['area_id'] = $request['area']['id'];
            $table = Table::where([
                ['id', '!=', $validated['id']],
                ['area_id', '=', $validated['area_id']],
                ['code', '=', $validated['code']]])->first();
            if($table != null)
                return response()->json(["message"=>"CODE_EXIST"]);
            $this->saveTable($validated, $request);
            
        }
        return response()->json(["message"=>"Done"]);
    }

    protected function saveTable($validated, $request){
        $table = Table::find($validated['id']);
        $table->fill($validated);
        $table->save();
    }

    protected function createTable($validated, $request){
        $table= Table::create($validated);
    }

    public function create()
    {
        $table  = new Table();
        return view('reservation::table.create', compact('table'));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $item = Table::find($id);

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
        $table  = Table::find($id);
        return view('reservation::table.edit', compact('table'));
    }
}
