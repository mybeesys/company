<?php

namespace Modules\Establishment\Http\Controllers;

use DB;
use Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Establishment\Http\Requests\UpdateCompanyRequest;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!get_company_id()) {
            return redirect()->back()->with('error', __('establishment::responses.no_company_found'));
        }
        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $countries = DB::connection('mysql')->table('countries')->get(['id', 'name_en', 'name_ar']);
        $countries = $countries->map(function ($country) {
            return [
                'id' => $country->id,
                'name' => session('locale') == 'ar' ? $country->name_ar : $country->name_en,
            ];
        });
        return view('establishment::company.settings.index', compact('company', 'countries'));
    }


    public function updateLiveValidation(UpdateCompanyRequest $request)
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function update(UpdateCompanyRequest $request, $id)
    {
        if (request()->ajax()) {
            try {
                DB::connection('mysql')->table('companies')->where('id', $id)->update($request->safe()->all());
                return response()->json(['message' => __('employee::responses.operation_success')]);
            } catch (\Throwable $e) {
                Log::error('company update failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->back()->with('error', __('establishment::responses.something_wrong_happened'));
            }
        }
    }
}
