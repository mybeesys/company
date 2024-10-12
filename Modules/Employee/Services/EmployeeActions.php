<?php

namespace Modules\Employee\Services;

use Carbon\Carbon;
use DB;
use File;
use Modules\Employee\Models\AdministrativeUser;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\EmployeeEstablishment;
use Modules\Employee\Models\Role;
use Modules\Employee\Models\Wage;
use Modules\Establishment\Models\Establishment;


class EmployeeActions
{

    public function __construct(protected $request)
    {
    }

    public function storeUpdateAdministrativeUser($employee_id)
    {
        $data = [
            'employee_id' => $employee_id,
            'userName' => $this->request->get('username'),
            'accountLocked' => $this->request->get('accountLocked') ?? false,
        ];

        if ($this->request->get('password')) {
            $data['password'] = $this->request->get('password');
        }
        return AdministrativeUser::updateOrCreate(['employee_id' => $employee_id], $data);
    }

    public function assignRolesWagesEstablishments(array $data, $employee)
    {
        $data = collect($data);

        $globalRoles_ids = $data->filter(fn($item) => $item['establishment'] === 'all')
            ->pluck('role')
            ->unique()
            ->toArray();

        $employee->syncRoles(Role::whereIn('id', $globalRoles_ids)->get());

        $wages_ids = [];
        $employeeEstablishment_ids = [];
        $data->each(function ($role_wage) use ($employee, &$wages_ids, &$employeeEstablishment_ids) {

            $role_id = $role_wage['role'];
            $wage_rate = array_key_exists('wage', $role_wage) ? ($role_wage['wage'] ?? 0) : 0;
            $establishment_id = $role_wage['establishment'] === 'all' ? null : $role_wage['establishment'];

            $wage = Wage::updateOrCreate(
                ['employee_id' => $employee->id, 'role_id' => $role_id, 'establishment_id' => $establishment_id],
                ['rate' => $wage_rate]
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
        EmployeeEstablishment::whereNotIn('id', $employeeEstablishment_ids)->delete();
        Wage::whereNotIn('id', $wages_ids)->delete();
    }

    public function storeImage($image, $oldimage = null)
    {
        $oldPath = public_path('storage/tenant' . tenancy()->tenant->id . '/' . $oldimage);

        if (File::exists($oldPath)) {
            File::delete($oldPath);
        }

        $imageName = 'profile_pictures/' . time() . '.' . $image->extension();
        $image->storeAs('', $imageName, 'public');
        return $imageName;
    }

    public function store($request)
    {
        $imageName = $request->has('image') ? $this->storeImage($request->image) : null;

        $employee = Employee::create($request->safe()->merge([
            'image' => $imageName,
            'employmentStartDate' => Carbon::parse($request->employmentStartDate)->format('Y-m-d')
        ])->all());

        !empty($request->role_wage_repeater) && $this->assignRolesWagesEstablishments($request->role_wage_repeater, $employee);

        $request->active_managment_fields_btn &&
            $this->storeUpdateAdministrativeUser($employee->id);
    }

    public function update($request, $employee)
    {
        $request->get('active_managment_fields_btn') ?
            $this->storeUpdateAdministrativeUser($employee->id) : $employee->administrativeUser->delete();

        !empty($request->get('role_wage_repeater')) && $this->assignRolesWagesEstablishments($request->get('role_wage_repeater'), $employee);

        $imageName = $request->has('image') ? $this->storeImage($request->image, $employee->image) : null;

        $data = $request->merge([
            'employmentStartDate' => Carbon::parse($request->get('employmentStartDate'))->format('Y-m-d'),
            'employmentEndDate' => $request->has('employmentEndDate') ? Carbon::parse($request->get('employmentEndDate'))->format('Y-m-d') : null
        ]);

        $data = $imageName ? array_merge($request, [
            'image' => $imageName,
        ]) : $request;

        return $employee->update($data->toArray());
    }
}