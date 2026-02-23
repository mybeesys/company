<?php

namespace Modules\Franchise\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Franchise\Entities\FranchiseContract;
use Yajra\DataTables\Facades\DataTables;
use Modules\Franchise\Models\FranchiseCompanies;

class FranchiseCompanyController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = FranchiseCompanies::query();

            if ($request->view_type == 'new_no_contract') {
                $query->doesntHave('contracts');
            } elseif ($request->view_type == 'active_contracts') {
                $query->whereHas('contracts', function ($q) {
                    $q->where('end_date', '>=', now()->format('Y-m-d'));
                });
            } elseif ($request->view_type == 'expired_contracts') {
                $query->whereHas('contracts', function ($q) {
                    $q->where('end_date', '<', now()->format('Y-m-d'));
                })->whereDoesntHave('contracts', function ($q) {
                    $q->where('end_date', '>=', now()->format('Y-m-d'));
                });
            }

            return Datatables::of($query)
                ->addColumn('actions', function ($row) {
                    $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">'
                        . __('franchise::lang.actions') .
                        '<i class="ki-outline ki-down fs-5 ms-1"></i></a>';

                    $actions .= '<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-150px py-4" data-kt-menu="true">';

                    $actions .= '<div class="menu-item px-3">
                    <a href="' . route('franchise.companies.show', $row->id) . '" class="menu-link px-3">
                        <i class="ki-outline ki-eye fs-4 me-2"></i> ' . __('franchise::lang.view') . '
                    </a>
                </div>';

                    $actions .= '<div class="menu-item px-3">
                    <a href="javascript:void(0)" onclick="editCompany(' . $row->id . ')" class="menu-link px-3">
                        <i class="ki-outline ki-pencil fs-4 me-2"></i> ' . __('franchise::lang.edit') . '
                    </a>
                </div>';


                    $actions .= '<div class="separator mt-3 opacity-75"></div>';

                    $actions .= '<div class="menu-item px-3">
                    <a href="javascript:void(0)" onclick="deleteCompany(' . $row->id . ', \'' . $row->name_ar . '\')" class="menu-link px-3 text-danger">
                        <i class="ki-outline ki-trash fs-4 me-2 text-danger"></i> ' . __('franchise::lang.delete') . '
                    </a>
                </div>';

                    $actions .= '</div>';
                    return $actions;
                })
                ->editColumn('city', function($row) {
                    return __('franchise::lang.cities.' . $row->city);
                })
                ->editColumn('status_label', function ($row) {
                    if ($row->contracts()->count() == 0) return '<span class="badge badge-light-warning">'.__('franchise::lang.new_no_contract').'</span>';
                    return $row->contracts()->where('end_date', '>=', now())->exists()
                        ? '<span class="badge badge-light-success">'.__('franchise::lang.active_contracts').'</span>'
                        : '<span class="badge badge-light-danger">'.__('franchise::lang.expired_contracts').'</span>';
                })
                ->rawColumns(['actions', 'status_label'])
                ->make(true);
        }
        return view('franchise::companies.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name_ar' => 'required|string',
            'name_en' => 'required|string',
            'city'    => 'required',
            'street'  => 'nullable',
            'national_address' => 'nullable',
            'vat_no'  => 'required|unique:franchise_companies,vat_no',
            'mobile'  => 'required',
            'tel'     => 'nullable',
            'email'   => 'required|email|unique:franchise_companies,email',
            'account' => 'required',
        ]);

        FranchiseCompanies::create($data);
        return response()->json(['success' => true, 'message' => __('franchise::lang.save')]);
    }

   public function show($id)
    {
        $company = FranchiseCompanies::with('contracts')->findOrFail($id);
        if (request()->ajax()) {
            return response()->json($company);
        }
        return view('franchise::companies.show', compact('company'));
    }


    public function update(Request $request, $id)
    {
        $company = FranchiseCompanies::findOrFail($id);
        $data = $request->validate([
            'name_ar' => 'required',
            'name_en' => 'required',
            'vat_no'  => 'required|unique:franchise_companies,vat_no,' . $id,
            'email'   => 'required|email|unique:franchise_companies,email,' . $id,
        ]);

        $company->update($request->all());
        return response()->json(['success' => true, 'message' => __('franchise::lang.save')]);
    }
}
