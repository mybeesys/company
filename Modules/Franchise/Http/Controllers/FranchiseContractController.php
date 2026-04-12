<?php

namespace Modules\Franchise\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Permission;
use Modules\Franchise\Models\FranchiseCompanies;
use Modules\Franchise\Models\FranchiseContract;
use Spatie\Permission\Models\Role;

class FranchiseContractController extends Controller
{


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

                // $excludedPermissions = [
                //     'Franchise Companies.all.show',
                //     'Franchise Companies.all.print',
                //     'Franchise Companies.all.create',
                //     'Franchise Companies.all.update',
                //     'Franchise Companies.all.delete',
                // ];

                // $allPermissionIds = Permission::whereIn('type', ['pos', 'ems'])
                //     ->whereNotIn('name', $excludedPermissions)
                //     ->pluck('id')
                //     ->toArray();

                // if (!empty($allPermissionIds)) {
                //     $employee->permissions()->sync($allPermissionIds);
                // }


                // $adminRole = DB::table('roles')->where('name', 'Admin')->first();
                // if ($adminRole) {
                //     DB::table('emp_employee_establishments_roles')->insert([
                //         'employee_id' => $employee->id,
                //         'role_id'     => $adminRole->id,
                //         'establishment_id' => null,
                //         'created_at'  => now(),
                //         'updated_at'  => now(),
                //     ]);
                // }



                $roleName = 'صلاحيات ممنوح';

                $role = DB::table('roles')->where('name', $roleName)->first();

                if (!$role) {
                    $roleId = DB::table('roles')->insertGetId([
                        'name' => $roleName,
                        'guard_name' => 'web',
                        'type' => 'ems',
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $excludedPermissions = [
                        'Franchise Companies.all.show',
                        'Franchise Companies.all.print',
                        'Franchise Companies.all.create',
                        'Franchise Companies.all.update',
                        'Franchise Companies.all.delete',
                    ];

                    $allPermissionIds = Permission::whereIn('type', ['pos', 'ems'])
                        ->whereNotIn('name', $excludedPermissions)
                        ->pluck('id')
                        ->toArray();

                    $roleModel = Role::find($roleId);
                    if ($roleModel && !empty($allPermissionIds)) {
                        $roleModel->permissions()->sync($allPermissionIds);
                    }
                } else {
                    $roleId = $role->id;
                }

                DB::table('emp_employee_establishments_roles')->insert([
                    'employee_id' => $employee->id,
                    'role_id'     => $roleId,
                    'establishment_id' => null,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                //////////////////////////////////////////////////////////



            }
        });

        return response()->json(['message' => __('franchise::lang.added_successfully')]);
    }
    public function destroy($id)
    {
        FranchiseContract::findOrFail($id)->delete();
        return response()->json(['message' => __('franchise::lang.deleted_successfully')]);
    }



    public function edit($id)
    {
        $contract = FranchiseContract::findOrFail($id);
        return response()->json($contract);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'contract_duration' => 'required|integer',
            'start_date' => 'required|date',
            'reality_fees' => 'required|numeric',
            'contract_file' => 'nullable|mimes:pdf,jpg,png',
        ]);

        $contract = FranchiseContract::findOrFail($id);

        $data = $request->only([
            'contract_duration',
            'start_date',
            'reality_fees',
            'unite_no',
            'notes'
        ]);

        $data['end_date'] = \Carbon\Carbon::parse($request->start_date)
            ->addMonths((int) $request->contract_duration);

        if ($request->hasFile('contract_file')) {
            if ($contract->contract_file && Storage::disk('public')->exists($contract->contract_file)) {
                Storage::disk('public')->delete($contract->contract_file);
            }

            $file = $request->file('contract_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $data['contract_file'] = $file->storeAs('franchise_contracts', $fileName, 'public');
        }

        $contract->update($data);

        return response()->json(['message' => __('franchise::lang.updated_successfully')]);
    }


    public function extend(Request $request, $id)
    {
        $request->validate([
            'extension_duration' => 'required|integer|min:1',
        ]);

        $contract = FranchiseContract::findOrFail($id);

        DB::transaction(function () use ($request, $contract) {
            $extensionMonths = (int) $request->extension_duration;
            $oldEndDate = $contract->end_date;
            $newEndDate = Carbon::parse($oldEndDate)->addMonths($extensionMonths);

            DB::table('franchise_contract_extensions')->insert([
                'contract_id' => $contract->id,
                'added_months' => $extensionMonths,
                'old_end_date' => $oldEndDate,
                'new_end_date' => $newEndDate,
                'created_by'   => auth()->user()->id ?? null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            $contract->contract_duration += $extensionMonths;
            $contract->end_date = $newEndDate;
            $contract->save();
        });

        return response()->json(['message' => 'تم تمديد العقد وتسجيل العملية بنجاح']);
    }

    public function getExtensionHistory($id)
    {
        $extensions = DB::table('franchise_contract_extensions')
            ->where('contract_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($extensions);
    }
}
