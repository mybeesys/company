<?php

namespace Modules\Employee\Services;

use Carbon\Carbon;
use File;
use Modules\Employee\Models\AdministrativeUser;
use Modules\Employee\Models\AdministrativeUserEstablishment;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\EmployeeEstablishment;
use Modules\Employee\Models\Role;
use Modules\Employee\Models\Wage;


class EmployeeActions
{

    public function __construct(protected $request)
    {
    }


    public static function getShowEditEmployee($id)
    {
        return Employee::with([
            'establishmentsPivot',
            'establishmentsPivot.wage' => function ($query) {
                $query->select('id', 'rate', 'wageType');
            },
            'establishmentsPivot.establishment' => function ($query) {
                $query->select('id', 'name');
            },
            'roles' => function ($query) {
                $query->select('roles.id', 'roles.name');
            },
            'roles.wage' => function ($query) use ($id) {
                $query->select('role_id', 'rate', 'wageType', 'establishment_id')->where('employee_id', $id);
            },
            'administrativeUser.permissionSets'
        ])->findOrFail($id);
    }


    public function storeUpdateAdministrativeUser($repeaterData, $employee_id)
    {
        $repeaterData = $repeaterData ? collect($repeaterData) : null;

        $data = [
            'employee_id' => $employee_id,
            'userName' => $this->request->get('username'),
            'accountLocked' => $this->request->get('accountLocked') ?? false,
        ];

        if ($this->request->get('password')) {
            $data['password'] = $this->request->get('password');
        }

        $user = AdministrativeUser::updateOrCreate(['employee_id' => $employee_id], $data);
        $administrativeUserEstablishment_ids = [];
        if ($repeaterData) {
            $repeaterData->each(function ($repData) use ($user, &$administrativeUserEstablishment_ids) {
                $administrativeUserEstablishment = AdministrativeUserEstablishment::updateOrCreate([
                    'user_id' => $user->id,
                    'establishment_id' => $repData['establishment']
                ], [
                    'permissionSet_id' => $repData['dashboardRole']
                ]);
                $administrativeUserEstablishment_ids[] = $administrativeUserEstablishment->id;
            });
            AdministrativeUserEstablishment::where('user_id', $user->id)->whereNotIn('id', $administrativeUserEstablishment_ids)->delete();
        } else {
            AdministrativeUserEstablishment::where('user_id', $user->id)->delete();
        }
    }

    public function assignRolesWagesEstablishments(array $data, $employee)
    {
        $data = collect($data);

        // Global roles
        $globalRoles_ids = $data->filter(fn($item) => $item['establishment'] === 'all')
            ->pluck('role')
            ->unique()
            ->toArray();

        $employee->syncRoles(Role::whereIn('id', $globalRoles_ids)->get());

        // Wages and establishments
        $wages_ids = [];
        $employeeEstablishment_ids = [];
        $data->each(function ($role_wage) use ($employee, &$wages_ids, &$employeeEstablishment_ids) {

            $role_id = $role_wage['role'];
            $wage_rate = array_key_exists('wage', $role_wage) ? ($role_wage['wage'] ?? 0) : 0;
            $wage_type = array_key_exists('wage_type', $role_wage) ? $role_wage['wage_type'] : null;
            $establishment_id = $role_wage['establishment'] === 'all' ? null : $role_wage['establishment'];

            $wage = Wage::updateOrCreate(
                ['employee_id' => $employee->id, 'role_id' => $role_id, 'establishment_id' => $establishment_id],
                ['rate' => $wage_rate, 'wageType' => $wage_type]
            );

            $wages_ids[] = $wage->id;
            if ($establishment_id) {
                $employeeEstablishment = EmployeeEstablishment::updateOrCreate(
                    [
                        'establishment_id' => $establishment_id,
                        'employee_id' => $employee->id,
                        'role_id' => $role_id
                    ],
                    ['wage_id' => $wage->id]
                );
                $employeeEstablishment_ids[] = $employeeEstablishment->id;
            }
        });
        EmployeeEstablishment::where('employee_id', $employee->id)->whereNotIn('id', $employeeEstablishment_ids)->delete();
        Wage::where('employee_id', $employee->id)->whereNotIn('id', $wages_ids)->delete();
    }

    public function storeImage($image, $oldImage = null)
    {
        $oldPath = public_path('storage/tenant' . tenancy()->tenant->id . '/' . $oldImage);

        if (File::exists($oldPath)) {
            File::delete($oldPath);
        }

        $imageName = 'profile_pictures/' . time() . '.' . $image->extension();
        $image->storeAs('', $imageName, 'public');
        return $imageName;
    }

    public function store()
    {
        $imageName = $this->request->has('image') ? $this->storeImage($this->request->image) : null;

        $employee = Employee::create($this->request->merge([
            'image' => $imageName,
            'employmentStartDate' => Carbon::parse($this->request->get('employmentStartDate'))->format('Y-m-d')
        ])->all());

        // Handling employee's POS roles and wages
        !empty($this->request->get('role_wage_repeater')) && $this->assignRolesWagesEstablishments($this->request->get('role_wage_repeater'), $employee);

        // Handling administrative User and their roles and establishments
        $this->request->get('active_management_fields_btn') &&
            $this->storeUpdateAdministrativeUser($this->request->get('dashboard_role_repeater'), $employee->id);
    }

    public function update($employee)
    {
        // Handling administrative User and their roles and establishments
        $this->request->get('active_management_fields_btn') ?
            $this->storeUpdateAdministrativeUser($this->request->get('dashboard_role_repeater'), $employee->id) : $employee?->administrativeUser?->delete();

        // Handling employee's POS roles and wages
        !empty($this->request->get('role_wage_repeater')) ? $this->assignRolesWagesEstablishments($this->request->get('role_wage_repeater'), $employee) : $this->unSyncAllRoles($employee);

        $imageName = $this->request->has('image') ? $this->storeImage($this->request->image, $employee->image) : null;

        $data = $this->request->merge([
            'employmentStartDate' => Carbon::parse($this->request->get('employmentStartDate'))->format('Y-m-d'),
            'employmentEndDate' => $this->request->has('employmentEndDate') ? Carbon::parse($this->request->get('employmentEndDate'))->format('Y-m-d') : null
        ]);

        $data = $imageName ? array_merge($this->request, [
            'image' => $imageName,
        ]) : $this->request;

        return $employee->update($data->toArray());
    }

    public function unSyncAllRoles($employee)
    {
        Wage::where('employee_id', $employee->id)->delete();
        $employee->syncRoles(null);
    }
}