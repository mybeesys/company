<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use File;
use Illuminate\Http\Request;
use Modules\Employee\Classes\Tables;
use Modules\Employee\Http\Requests\StoreEmployeeRequest;
use Modules\Employee\Http\Requests\UpdateEmployeeRequest;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Role;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $local = session()->get('locale');
        if ($request->ajax()) {
            $employees = Employee::
                select('id', 'name', 'name_en', 'phoneNumber', 'employmentStartDate', 'employmentEndDate', 'isActive', 'deleted_at');

            if ($request->has('deleted_records') && !empty($request->deleted_records)) {
                $request->deleted_records == 'only_deleted_records'
                    ? $employees->onlyTrashed()
                    : ($request->deleted_records == 'with_deleted_records' ? $employees->withTrashed() : null);
            }
            return Tables::getEmployeeTable($employees);
        }
        $columns = Tables::getEmployeeColumns();
        return view('employee::employee.index', compact('columns', 'local'));
    }

    function generatePin()
    {
        $number = mt_rand(10000, 99999);

        if ($this->barcodeNumberExists($number)) {
            return $this->generatePin();
        }

        return response()->json(['data' => $number]);
    }

    function barcodeNumberExists($number)
    {
        return Employee::where('pin', $number)->exists();
    }

    public function createLiveValidation(StoreEmployeeRequest $request)
    {
    }

    public function updateLiveValidation(UpdateEmployeeRequest $request)
    {
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::all()->select('id', 'name');
        return view('employee::employee.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request)
    {
        DB::beginTransaction();
        if ($request->has('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->storeAs('profile_pictures', $imageName, 'public');
        } else {
            $imageName = null;
        }

        
        
        $employee = Employee::create($request->safe()->merge(['image' => "profile_pictures/{$imageName}"])->all());
        
        if ($request->has('role')) {
            $role = Role::findById($request->role);
            $employee->assignRole($role);
        }

        DB::commit();
        if ($employee) {
            return redirect()->route('employees.index')->with('success', __('employee::responses.employee_created_successfully'));
        } else {
            return redirect()->route('employees.index')->with('error', __('employee::responses.something_wrong_happened'));
        }
    }

    /**
     * Show the specified resource.
     */
    public function show(Employee $employee)
    {
        return view('employee::employee.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        $roles = Role::all()->select('id', 'name');
        return view('employee::employee.edit', compact('employee', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        DB::beginTransaction();
        $filteredRequest = array_filter($request->safe()->all(), function ($value) {
            return !is_null($value);
        });

        if ($request->has('role')) {
            $role = Role::findById($request->role);
            if ($employee->roles()->first() && $employee->roles()->first()->id != $request->role) {
                $employee->removeRole($employee->roles()->first());
            }
            $employee->assignRole($role);
        }

        if ($request->has('image')) {
            $oldPath = public_path('storage/tenant' . tenancy()->tenant->id . '/' . $employee->image);
            File::exists($oldPath) ?? File::delete($oldPath);
            $imageName = time() . '.' . $request->image->extension();
            $request->image->storeAs('profile_pictures', $imageName, 'public');

            $updated = $employee->update(array_merge($filteredRequest, ['image' => "profile_pictures/{$imageName}"]));
        } else {
            $updated = $employee->update($filteredRequest);
        }
        DB::commit();
        if ($updated) {
            return redirect()->route('employees.index')->with('success', __('employee::responses.employee_updated_successfully'));
        } else {
            return redirect()->route('employees.index')->with('error', __('employee::responses.something_wrong_happened'));
        }
    }

    public function restore($id)
    {
        $restore = Employee::where('id', $id)->restore();
        if ($restore) {
            return response()->json(['message' => __('employee::responses.employee_restored_successfully')]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function softDelete(Employee $employee)
    {
        $delete = $employee->delete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.employee_deleted_successfully')]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }

    public function forceDelete($id)
    {
        $delete = Employee::where('id', $id)->forceDelete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.employee_deleted_successfully')]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}
