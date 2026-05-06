<?php

namespace Modules\Franchise\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Establishment\Models\Establishment;
use Modules\Franchise\Models\FranchiseCompanies;
use Yajra\DataTables\Facades\DataTables;

class FranchiseBranchController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Establishment::withoutGlobalScope('excludeFranchise')
                ->where('is_franchise', 1)
                ->whereNotNull('franchise_id')
                ->with('franchise');

            return Datatables::of($query)
                ->addColumn('franchise_name', function ($row) {
                    return $row->franchise ? $row->franchise->name_ar : '-';
                })
                ->addColumn('location_info', function ($row) {
                    return $row->city.($row->region ? ' / '.$row->region : '');
                })
                ->addColumn('status_label', function ($row) {
                    $active = __('franchise::lang.active') ?? 'مفعل';
                    $inactive = __('franchise::lang.inactive') ?? 'غير مفعل';

                    return $row->is_active ?
                        '<span class="badge badge-light-success">'.$active.'</span>' :
                        '<span class="badge badge-light-danger">'.$inactive.'</span>';
                })
                ->addColumn('actions', function ($row) {
                    return '
                    <div class="d-flex justify-content-end">
                        <button onclick="editBranch('.$row->id.')" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                            <i class="ki-outline ki-pencil fs-2"></i>
                        </button>
                        <button onclick="deleteBranch('.$row->id.')" class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm">
                            <i class="ki-outline ki-trash fs-2"></i>
                        </button>
                    </div>';
                })
                ->rawColumns(['status_label', 'actions'])
                ->make(true);
        }
        $franchises = FranchiseCompanies::whereHas('activeContract')
            ->get()
            ->filter(function ($company) {
                $activeContract = $company->activeContract;

                return $company->branches()->count() < $activeContract->unite_no;
            });

        return view('franchise::branches.index', compact('franchises'));
    }

    public function store(Request $request)
    {
        ini_set('display_errors', 0);
        $request->validate([
            'franchise_id' => 'required',
            'code' => 'required|unique:est_establishments,code',
            'name' => 'required',
            'name_en' => 'required',
            'city' => 'required',
        ]);

        $company = FranchiseCompanies::with('activeContract')->findOrFail($request->franchise_id);
        $activeContract = $company->activeContract;

        if (! $activeContract) {
            return response()->json(['message' => 'هذه الشركة ليس لديها عقد نشط حالياً'], 422);
        }

        $currentBranchesCount = $company->branches()->count();

        if ($currentBranchesCount >= $activeContract->unite_no) {
            return response()->json(['message' => 'عذراً، تم الوصول للحد الأقصى من الفروع المسموح بها لهذا العقد'], 422);
        }
        $data = $request->except(['logo', '_token', '_method', 'branch_id']);
        $data['is_franchise'] = 1;

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        Establishment::create($data);

        return response()->json(['success' => true]);
    }

    public function edit($id)
    {
        $branch = Establishment::withoutGlobalScope('excludeFranchise')->findOrFail($id);

        return response()->json($branch);
    }

    public function update(Request $request, $id)
    {
        $branch = Establishment::withoutGlobalScope('excludeFranchise')->findOrFail($id);

        $request->validate([
            'code' => 'required|unique:est_establishments,code,'.$id,
        ]);

        $data = $request->except(['logo', '_token', '_method', 'branch_id']);

        if ($request->hasFile('logo')) {
            if ($branch->logo) {
                Storage::disk('public')->delete($branch->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $branch->update($data);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $branch = Establishment::withoutGlobalScope('excludeFranchise')->findOrFail($id);
        $branch->delete();

        return response()->json(['success' => true]);
    }
}
