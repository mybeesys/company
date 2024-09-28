<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Yajra\DataTables\DataTables;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $employees = Employee::select('id', 'firstName', 'lastName', 'phoneNumber', 'employmentStartDate', 'employmentEndDate', 'isActive');

            return DataTables::of($employees)
                ->editColumn('id', function ($row) {
                    return "<div class='badge badge-light-info'>
                                     {$row->id} 
                            </div>";
                })

                ->addColumn('actions', function ($row) {
                    return '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions<i class="ki-outline ki-down fs-5 ms-1"></i></a>
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
                    <div class="menu-item px-3">
                        <a href="#" class="menu-link px-3">Edit</a>
                    </div>
                    <div class="menu-item px-3">
                        <a href="#" class="menu-link px-3">Delete</a>
                    </div>
                </div>';
                })
                ->editColumn('isActive', function ($employee) {
                    return $employee->isActive
                        ? '<div class="badge badge-light-success">' . __("employee::fields.active") . '</div>'
                        : '<div class="badge badge-light-danger">' . __("employee::fields.active") . '</div>';
                })
                ->rawColumns(['actions', 'isActive', 'id'])
                ->make(true);
        }

        return view('employee::employee.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('employee::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
    public function edit($id)
    {
        return view('employee::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
