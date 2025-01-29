<?php

namespace Modules\Screen\Http\Controllers;

use App\Http\Controllers\Controller;
use File;
use Illuminate\Http\Request;
use Modules\Screen\Classes\PromoTable;
use Modules\Screen\Models\Promo;
use Modules\Screen\Services\PromoActions;

class PromoController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $promos = Promo::all();
            return PromoTable::getPromoIndexTable($promos);
        }
    }

    public function playlistIndex(Request $request)
    {
        if ($request->ajax()) {
            $promos = Promo::all();
            return PromoTable::getPlaylistPromoTable($promos);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'promo' => ['required', 'mimes:jpeg,png,mp4', 'max:120000'],
        ]);
        $action = new PromoActions();
        $action->storePromo($validated);
        return response()->json(['message' => __('employee::responses.operation_success')]);
    }

    public function update(Request $request, Promo $promo)
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $promo->update(['name' => $validated['name']]);

        return response()->json(['message' => __('employee::responses.updated_successfully', ['name' => __('screen::fields.promo')])]);
    }

    public function destroy(Promo $promo)
    {
        $file = public_path('storage/tenant' . tenancy()->tenant->id . '/' . $promo->path);

        if (File::exists($file)) {
            File::delete($file);
        }
        $delete = $promo->delete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('screen::fields.promo')])]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}
