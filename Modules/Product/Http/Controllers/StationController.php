<?php 
namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\Station;

class StationController extends Controller
{
    public function getStations()
    {
        $stations = Station::all();
        return response()->json($stations);
    }
}
