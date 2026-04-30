<?php

namespace Modules\Screen\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Modules\Establishment\Models\Establishment;
use Modules\Screen\Models\Device;
use Modules\Screen\Models\Playlist;
use Modules\Screen\Models\Promo;

class MainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $promos = Promo::all();
        $playlistsCount = Playlist::count();
        $establishments = Establishment::active()->notMain()->select('name', 'id')->get();
        $hasEstablishmentColumn = Schema::hasColumn('screen_devices', 'establishment_id');
        $devices = $hasEstablishmentColumn
            ? Device::with('establishment')->select('code', 'id', 'establishment_id')->get()
            : Device::select('code', 'id')->get();

        return view('screen::main.index', compact('promos', 'establishments', 'devices', 'playlistsCount', 'hasEstablishmentColumn'));
    }


}
