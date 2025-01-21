<?php

namespace Modules\Reservation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|numeric',
            'code' => 'required|string',
            'area_id' => 'nullable|numeric',
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
            $table = Table::where([['area_id', '=', $validated['area_id']],
                                        ['code', '=', $validated['code']]])->first();
            if($table != null)
                return response()->json(["message"=>"CODE_EXIST"]);
            $this->createModifier($validated, $request);   
        }
        else
        {
            //dd($validated['id'].' '.$validated['class_id'].' '.$validated['name_ar']);
            $table = Table::where([
                ['id', '!=', $validated['id']],
                ['area_id', '=', $validated['area_id']],
                ['code', '=', $validated['code']]])->first();
            if($table != null)
                return response()->json(["message"=>"CODE_EXIST"]);
            $this->saveModifier($validated, $request);
            
        }
        return response()->json(["message"=>"Done"]);
    }

    protected function saveModifier($validated, $request){
        $table = Table::find($validated['id']);
        $table->area_id  = $validated['area_id'];
        $table->code     = $validated['code'];
        $table->steating_capacity    = $validated['steating_capacity'];
        $table->table_status   = $validated['table_status'];
        $table->save();
    }

    protected function createModifier($validated, $request){
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
