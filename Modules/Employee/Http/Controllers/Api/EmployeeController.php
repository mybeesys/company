<?php

namespace Modules\Employee\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;
use Modules\Employee\Transformers\Collections\EmployeeCollection;

class EmployeeController extends Controller
{


    public function index(Request $request)
    {
        $request->validate([
            'establishment_id' => ['nullable', 'exists:establishment_establishments,id']
        ]);
        $employees = Employee::with(['posRoles', 'defaultEstablishment', 'wage', 'allowances', 'deductions'])
            ->when($request->query('establishment_id'), function ($query) use ($request) {
                $query->whereHas('defaultEstablishment', fn($query) => $query->where('id', $request->query('establishment_id')));
            })->get();
        return new EmployeeCollection($employees);
    }
}