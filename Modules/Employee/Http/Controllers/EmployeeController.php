<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use File;
use Illuminate\Http\Request;
use Modules\Employee\Classes\Tables;
use Modules\Employee\Http\Requests\CreateEmployeeRequest;
use Modules\Employee\Http\Requests\UpdateEmployeeRequest;
use Modules\Employee\Models\Employee;
use Storage;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $employees = Employee::
                select('id', 'firstName', 'lastName', 'phoneNumber', 'employmentStartDate', 'employmentEndDate', 'isActive', 'deleted_at');

            if ($request->has('deleted_records') && !empty($request->deleted_records)) {
                $request->deleted_records == 'only_deleted_records'
                    ? $employees->onlyTrashed()
                    : ($request->deleted_records == 'with_deleted_records' ? $employees->withTrashed() : null);
            }

            return Tables::getEmployeeTable($employees);
        }

        return view('employee::employee.index');
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

    public function createLiveValidation(CreateEmployeeRequest $request)
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
        return view('employee::employee.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateEmployeeRequest $request)
    {
        DB::beginTransaction();
        if ($request->has('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->storeAs('profile_pictures', $imageName, 'public');
        } else {
            $imageName = null;
        }

        $employee = Employee::create($request->safe()->merge(['image' => "profile_pictures/{$imageName}"])->all());

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
    public function show($id)
    {
        return view('employee::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        return view('employee::employee.edit', compact('employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        DB::beginTransaction();
        if ($request->has('image')) {
            $oldPath = public_path('storage/tenant' . tenancy()->tenant->id . '/' . $employee->image);
            File::exists($oldPath) ?? File::delete($oldPath);
            $imageName = time() . '.' . $request->image->extension();
            $request->image->storeAs('profile_pictures', $imageName, 'public');

            $updated = $employee->update($request->safe()->merge(['image' => "profile_pictures/{$imageName}"])->all());
        } else {
            $updated = $employee->update($request->safe()->all());
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
