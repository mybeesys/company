<?php

namespace Modules\Franchise\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Permission;
use Modules\Franchise\Models\FranchiseCompanies;
use Modules\Franchise\Models\FranchiseContract;

class FranchiseContractController extends Controller
{
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'franchise_id' => 'required',
    //         'contract_duration' => 'required|integer',
    //         'start_date' => 'required|date',
    //         'reality_fees' => 'required|numeric',
    //         'contract_file' => 'nullable|mimes:pdf,jpg,png|max:10240',
    //     ]);
    //     $exists = FranchiseContract::where('franchise_id', $request->franchise_id)
    //         ->where('status', 'active')
    //         ->exists();

    //     if ($exists) {
    //         return response()->json(['message' => 'لا يمكن إضافة عقد جديد وهناك عقد نشط حالياً'], 422);
    //     }
    //     $data = $request->only([
    //         'franchise_id',
    //         'contract_duration',
    //         'start_date',
    //         'reality_fees',
    //         'unite_no',
    //         'notes'
    //     ]);

    //     $data['end_date'] = \Carbon\Carbon::parse($request->start_date)
    //         ->addMonths((int) $request->contract_duration);

    //     if ($request->hasFile('contract_file')) {
    //         $file = $request->file('contract_file');
    //         $fileName = time() . '_' . $file->getClientOriginalName();
    //         $data['contract_file'] = $file->storeAs('franchise_contracts', $fileName, 'public');
    //     }

    //     FranchiseContract::create($data);

    //     return response()->json(['message' => __('franchise::lang.added_successfully')]);
    //     return response()->json(['success' => true, 'message' => __('franchise::lang.success_msg')]);
    // }


    public function store(Request $request)
    {
        $request->validate([
            'franchise_id' => 'required',
            'contract_duration' => 'required|integer',
            'start_date' => 'required|date',
            'reality_fees' => 'required|numeric',
            'contract_file' => 'nullable|mimes:pdf,jpg,png',
        ]);

        $exists = FranchiseContract::where('franchise_id', $request->franchise_id)
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'لا يمكن إضافة عقد جديد وهناك عقد نشط حالياً'], 422);
        }

        $franchise = FranchiseCompanies::findOrFail($request->franchise_id);

        $data = $request->only([
            'franchise_id',
            'contract_duration',
            'start_date',
            'reality_fees',
            'unite_no',
            'notes'
        ]);

        $data['end_date'] = \Carbon\Carbon::parse($request->start_date)
            ->addMonths((int) $request->contract_duration);

        if ($request->hasFile('contract_file')) {
            $file = $request->file('contract_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $data['contract_file'] = $file->storeAs('franchise_contracts', $fileName, 'public');
        }

        DB::transaction(function () use ($data, $franchise) {
            FranchiseContract::create($data);

            $employeeExists = Employee::where('franchise_id', $franchise->id)->exists();

            if (!$employeeExists) {
                $employee = Employee::create([
                    'name' => $franchise->name_ar,
                    'name_en' => $franchise->name_en,
                    'last_name'  => 'Admin',
                    'email'      => $franchise->email,
                    'user_name'      => $franchise->email,
                    'phone_number' => $franchise->tel,
                    'password'   => bcrypt('123456789'),
                    'franchise_id' => $franchise->id,
                    'employment_start_date' => now()->format('Y-m-d'),
                    'created_by' => auth()->user()->id ?? null,
                    'is_active'  => 1,
                    'ems_access' => 1,
                ]);

                $allPermissionIds = Permission::whereIn('type', ['pos', 'ems'])->pluck('id')->toArray();

                if (!empty($allPermissionIds)) {
                    $employee->permissions()->sync($allPermissionIds);
                }


                $adminRole = DB::table('roles')->where('name', 'Admin')->first();
                if ($adminRole) {
                    DB::table('emp_employee_establishments_roles')->insert([
                        'employee_id' => $employee->id,
                        'role_id'     => $adminRole->id,
                        'establishment_id' => null,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }
        });

        return response()->json(['message' => __('franchise::lang.added_successfully')]);
    }
    public function destroy($id)
    {
        FranchiseContract::findOrFail($id)->delete();
        return response()->json(['message' => __('franchise::lang.deleted_successfully')]);
    }
}
