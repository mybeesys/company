<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Establishment\Models\Establishment;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\Station;

class StationController extends Controller
{
    public function getStations()
    {
        //$stations = Station::all();
        $stations = Establishment::select('name', 'name_en','id')->get();
        return response()->json($stations);
    }
}
