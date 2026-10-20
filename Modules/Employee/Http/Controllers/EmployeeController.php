<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Employee\Classes\EmployeeTable;
use Modules\Employee\Http\Requests\StoreEmployeeRequest;
use Modules\Employee\Http\Requests\UpdateEmployeeRequest;
use Modules\Employee\Models\DashboardRole;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\PayrollAdjustmentType;
use Modules\Employee\Models\Permission;
use Modules\Employee\Models\PosRole;
use Modules\Employee\Services\DashboardRoleService;
use Modules\Employee\Services\EmployeeActions;
use Modules\Establishment\Models\Establishment;

class EmployeeController extends Controller
{
    protected ?Collection $establishments = null;

    protected ?Collection $dashboardRoles = null;

    protected ?Collection $posRoles = null;

    protected ?Collection $allowances_types = null;

    public function generatePin()
    {
        $number = mt_rand(10000, 99999);
        if (Employee::where('pin', $number)->exists()) {
            return $this->generatePin();
        }

        return response()->json(['data' => $number]);
    }

    public function createLiveValidation(StoreEmployeeRequest $request) {}

    public function updateLiveValidation(UpdateEmployeeRequest $request) {}

    private function getEmployeeViewData(int $id): array
    {
        return [
            'employee' => Employee::with(['allowances' => fn ($query) => $query->always(), 'dashboardRoles', 'posRoles'])->findOrFail($id),
            'posRoles' => $this->posRoles(),
            'dashboardRoles' => $this->dashboardRoles(),
            'establishments' => $this->establishments(),
            'allowances_types' => $this->allowancesTypes(),
        ];
    }

    public function index(Request $request)
    {
        // Keep the listing lean — DataTables never needs permission pivots.
        $employees = Employee::query()->select(
            'id',
            'name',
            'name_en',
            'phone_number',
            'image',
            'employment_start_date',
            'employment_end_date',
            'pos_is_active',
            'ems_access',
            'deleted_at'
        );

        if ($request->ajax()) {
            if ($request->has('deleted_records') && ! empty($request->deleted_records)) {
                $request->deleted_records == 'only_deleted_records'
                    ? $employees->onlyTrashed()
                    : ($request->deleted_records == 'with_deleted_records' ? $employees->withTrashed() : null);
            }

            return EmployeeTable::getEmployeeTable($employees);
        }

        // Initial HTML only: shell + modal catalogs. Rows load via AJAX.
        $columns = EmployeeTable::getEmployeeColumns();
        $permissions = Permission::where('type', 'pos')
            ->orderByRaw('FIELD(name, "select_all_permissions") DESC')
            ->get(['id', 'name', 'name_ar', 'description', 'description_ar']);
        $modules = DashboardRoleService::getModulesPermissions();
        $posRoles = $this->posRoles();
        $establishments = $this->establishments();
        $dashboardRoles = $this->dashboardRoles();

        return view('employee::employee.index', compact(
            'columns',
            'permissions',
            'modules',
            'posRoles',
            'establishments',
            'dashboardRoles'
        ));
    }

    public function create()
    {
        return view(
            'employee::employee.create',
            [
                'posRoles' => $this->posRoles(),
                'dashboardRoles' => $this->dashboardRoles(),
                'establishments' => $this->establishments(),
                'allowances_types' => $this->allowancesTypes(),
            ]
        );
    }

    public function store(StoreEmployeeRequest $request)
    {
        return DB::transaction(function () use ($request) {
            try {
                $filteredRequest = $request->safe()->collect()->filter(function ($item) {
                    return isset($item);
                });
                $storeEmployee = new EmployeeActions($filteredRequest);
                $storeEmployee->store();

                return to_route('employees.index')->with('success', __('employee::responses.created_successfully', ['name' => __('employee::fields.employee')]));
            } catch (\Throwable $e) {
                Log::error('Employee creation failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return redirect()->back()->with('error', __('employee::responses.something_wrong_happened'));
            }
        });
    }

    public function show(int $id)
    {
        return view('employee::employee.show', $this->getEmployeeViewData($id));
    }

    public function edit(int $id)
    {
        return view('employee::employee.edit', $this->getEmployeeViewData($id));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {

        return DB::transaction(function () use ($request, $employee) {
            try {
                $filteredRequest = $request->safe()->collect()->filter(function ($item) {
                    return isset($item);
                });
                $updateEmployee = new EmployeeActions($filteredRequest);
                $updateEmployee->update($employee);

                return to_route('employees.index')->with('success', __('employee::responses.updated_successfully', ['name' => __('employee::fields.employee')]));
            } catch (\Throwable $e) {
                Log::error('Employee updating failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return redirect()->back()->with('error', __('employee::responses.something_wrong_happened'));
            }
        });
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

    public function softDelete(Employee $employee)
    {
        $delete = $employee->delete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('employee::fields.employee')])]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }

    public function forceDelete($id)
    {
        $delete = Employee::where('id', $id)->forceDelete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('employee::fields.employee')])]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }

    protected function establishments(): Collection
    {
        return $this->establishments ??= Establishment::active()->get(['id', 'name']);
    }

    protected function dashboardRoles(): Collection
    {
        return $this->dashboardRoles ??= DashboardRole::get(['id', 'name']);
    }

    protected function posRoles(): Collection
    {
        return $this->posRoles ??= PosRole::get(['id', 'name']);
    }

    protected function allowancesTypes(): Collection
    {
        return $this->allowances_types ??= PayrollAdjustmentType::allowance()->get();
    }
}
