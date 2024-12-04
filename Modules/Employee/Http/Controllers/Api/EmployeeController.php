<?php

namespace Modules\Employee\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Employee\Models\Employee;
use Modules\Employee\Transformers\Collections\EmployeeCollection;

class EmployeeController extends Controller{
    

    public function index()
    {
        $employees = Employee::all();
        return new EmployeeCollection($employees);
    }
}