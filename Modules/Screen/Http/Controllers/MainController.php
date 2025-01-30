<?php

namespace Modules\Screen\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Establishment\Models\Establishment;
use Modules\Screen\Models\Device;
use Modules\Screen\Models\Promo;

class MainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $promos = Promo::all();
        $establishments = Establishment::active()->notMain()->select('name', 'id')->get();
        $devices = Device::select('code', 'id')->get();

        return view('screen::main.index', compact('promos', 'establishments', 'devices'));
    }


}
