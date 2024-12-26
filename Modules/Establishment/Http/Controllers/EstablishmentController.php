<?php

namespace Modules\Establishment\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Log;
use Modules\Establishment\Classes\EstablishmentTable;
use Modules\Establishment\Http\Requests\StoreEstablishmentRequest;
use Modules\Establishment\Models\Establishment;
use Modules\Establishment\Services\EstablishmentActions;

class EstablishmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $establishments = Establishment::select('id', 'name', 'name_en', 'address', 'is_main', 'parent_id', 'city', 'region', 'contact_details', 'is_active', 'deleted_at');
        if ($request->ajax()) {

            if ($request->has('deleted_records') && !empty($request->deleted_records)) {
                $request->deleted_records == 'only_deleted_records'
                    ? $establishments->onlyTrashed()
                    : ($request->deleted_records == 'with_deleted_records' ? $establishments->withTrashed() : null);
            }
            return EstablishmentTable::getEstablishmentTable($establishments);
        }
        $establishments = $this->getLevels();
        $columns = EstablishmentTable::getEstablishmentColumns();
        return view('establishment::establishment.index', compact('columns', 'establishments'));
    }


    public function createLiveValidation(StoreEstablishmentRequest $request)
    {
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $establishments = Establishment::where('is_main', true)->active()->get(['id', 'name', 'name_en']);
        return view('establishment::establishment.create', compact('establishments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEstablishmentRequest $request)
    {
        return DB::transaction(function () use ($request) {
            try {
                $filteredRequest = $request->safe()->collect()->filter(function ($item) {
                    return isset($item);
                });
                $storeEstablishment = new EstablishmentActions($filteredRequest);
                $storeEstablishment->store();
                return to_route('establishments.index')->with('success', __('employee::responses.created_successfully', ['name' => __('establishment::fields.establishment')]));
            } catch (\Throwable $e) {
                Log::error('Establishment creation failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->back()->with('error', __('establishment::responses.something_wrong_happened'));
            }
        });
    }



    // this function return all categories with unspecified number of categories levels
    public function getLevels()
    {

        $establishments = Establishment::with('children')->whereNull('parent_id')->get();
        $allWithChildren = [];
        foreach ($establishments as $establishment) {
            $allWithChildren = array_merge($allWithChildren, [
                $establishment,
                ...$establishment->getAllDescendants()
                    ->whereNull('parent_id')
            ]);
        }

        return $allWithChildren;
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        $establishment = Establishment::with('children')->findOrFail($id);
        $establishments = Establishment::where('is_main', true)->active()->whereNot('id', $establishment->id)->whereNotIn('id', $establishment->children->pluck('id'))->get(['id', 'name', 'name_en']);
        return view('establishment::establishment.edit', compact('establishment', 'establishments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreEstablishmentRequest $request, Establishment $establishment)
    {
        return DB::transaction(function () use ($request, $establishment) {
            try {
                $filteredRequest = $request->safe()->collect()->filter(function ($item) {
                    return isset($item);
                });
                $updateEstablishment = new EstablishmentActions($filteredRequest);
                $updateEstablishment->update($establishment);
                return to_route('establishments.index')->with('success', __('establishment::responses.updated_successfully', ['name' => __('establishment::fields.establishment')]));
            } catch (\Throwable $e) {
                Log::error('Establishment updating failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->back()->with('error', __('establishment::responses.something_wrong_happened'));
            }
        });
    }

    public function softDelete(Establishment $establishment)
    {
        $delete = $establishment->delete();
        if ($delete) {
            return response()->json(['message' => __('establishment::responses.deleted_successfully', ['name' => __('establishment::fields.establishment')])]);
        } else {
            return response()->json(['error' => __('establishment::responses.something_wrong_happened')], 500);
        }
    }

    public function forceDelete($id)
    {
        $delete = Establishment::where('id', $id)->forceDelete();
        if ($delete) {
            return response()->json(['message' => __('establishment::responses.deleted_successfully', ['name' => __('establishment::fields.establishment')])]);
        } else {
            return response()->json(['error' => __('establishment::responses.something_wrong_happened')], 500);
        }
    }

    public function restore($id)
    {
        $restore = Establishment::where('id', $id)->restore();
        if ($restore) {
            return response()->json(['message' => __('employee::responses.employee_restored_successfully')]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}
