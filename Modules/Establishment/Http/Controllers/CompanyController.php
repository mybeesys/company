<?php

namespace Modules\Establishment\Http\Controllers;

use Log;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Establishment\Http\Requests\UpdateCompanyRequest;
use Illuminate\Support\Facades\DB;

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


    public function updateLiveValidation(UpdateCompanyRequest $request) {}

    /**
     * Store a newly created resource in storage.
     */
    public function update(UpdateCompanyRequest $request, $id)
    {
        if (request()->ajax()) {
            try {
                $company = DB::connection('mysql')->table('companies')->where('id', $id)->first();
                if (!$company) {
                    return response()->json(['error' => 'Company not found'], 404);
                }
                $dataToUpdate = $request->safe()->except('email');
                DB::connection('mysql')->table('companies')->where('id', $id)->update($dataToUpdate);
                $userId = $company->user_id;
                if ($request->has('email') && $request->input('email')) {
                    DB::connection('mysql')->table('users')->where('id', $userId)->update(['email' => $request->input('email')]);
                }

                return response()->json(['message' => __('employee::responses.operation_success')]);
            } catch (\Throwable $e) {
                Log::error('company update failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json(['error' => __('establishment::responses.something_wrong_happened')], 500);
            }
        }
    }
}
