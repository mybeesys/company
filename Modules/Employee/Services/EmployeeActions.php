<?php

namespace Modules\Employee\Services;

use Carbon\Carbon;
use File;
use Modules\Employee\Models\PayrollAdjustment;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Wage;


class EmployeeActions
{

    public function __construct(protected $request)
    {
    }

    public function assignRolesEstablishments($PosRepeaterData, $dashboardRepeaterData, $employee)
    {
        $PosRepeaterData = $PosRepeaterData ? collect($PosRepeaterData) : null;
        $dashboardRepeaterData = $dashboardRepeaterData ? collect($dashboardRepeaterData) : null;

        $employee->allRoles()->detach();

        $processRepeaterData = function ($repeaterData, $roleKey, $employee) {
            $repeaterData->each(function ($repData) use ($employee, $roleKey) {
                $establishment_id = isset($repData['establishment']) ? ($repData['establishment'] === 'all' ? null : $repData['establishment']) : null;
                $employee->allRoles()->attach($repData[$roleKey], ['establishment_id' => $establishment_id]);
            });
        };

        if ($dashboardRepeaterData) {
            $processRepeaterData($dashboardRepeaterData, 'dashboardRole', $employee);
        }

        if ($PosRepeaterData) {
            $processRepeaterData($PosRepeaterData, 'posRole', $employee);
        }
    }


    public function assignEmployeeWages($wages, $employee_id)
    {
        $ids = [];
        foreach ($wages as $wage) {
            if (isset($wage['wage_id'])) {
                $ids[] = $wage['wage_id'];
                Wage::find($wage['wage_id'])->update([
                    'establishment_id' => $wage['establishment'],
                    'rate' => $wage['wage'],
                    'wage_type' => $wage['wage_type'],
                ]);
            } else {
                $ids[] = Wage::create([
                    'employee_id' => $employee_id,
                    'establishment_id' => $wage['establishment'],
                    'rate' => $wage['wage'],
                    'wage_type' => $wage['wage_type'],
                ])->id;
            }
            Wage::where('employee_id', $employee_id)->whereNotIn('id', $ids)->delete();
        }
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
            'employment_start_date' => Carbon::parse($this->request->get('employment_start_date'))->format('Y-m-d')
        ])->all());

        $this->assignRolesEstablishments($this->request->get('pos_role_repeater'), $this->request->get('dashboard_role_repeater'), $employee);

        !empty($this->request->get('wage_repeater')) && $this->assignEmployeeWages($this->request->get('wage_repeater'), $employee->id);

        !empty($this->request->get('allowance_repeater')) && $this->storeUpdateEmployeeAllowances($this->request->get('allowance_repeater'), $employee->id);
    }

    public function update($employee)
    {
        $this->assignRolesEstablishments($this->request->get('pos_role_repeater'), $this->request->get('dashboard_role_repeater'), $employee);

        !empty($this->request->get('allowance_repeater')) ? $this->storeUpdateEmployeeAllowances($this->request->get('allowance_repeater'), $employee->id) : PayrollAdjustment::where('type', 'allowance')->where('employee_id', $employee->id)->delete();

        !empty($this->request->get('wage_repeater')) ? $this->assignEmployeeWages($this->request->get('wage_repeater'), $employee->id) : Wage::where('employee_id', $employee->id)->delete();

        $imageName = $this->request->has('image') ? $this->storeImage($this->request->image, $employee->image) : null;

        $data = $this->request->merge([
            'employment_start_date' => Carbon::parse($this->request->get('employment_start_date'))->format('Y-m-d'),
            'employment_end_date' => $this->request->has('employment_end_date') ? Carbon::parse($this->request->get('employment_end_date'))->format('Y-m-d') : null
        ]);

        $data = $imageName ? array_merge($this->request, [
            'image' => $imageName,
        ]) : $this->request;

        return $employee->update($data->toArray());
    }

    public function storeUpdateEmployeeAllowances($allowances, $employee_id)
    {
        $ids = [];
        foreach ($allowances as $allowance) {
            if (isset($allowance['allowance_id'])) {
                $ids[] = $allowance['allowance_id'];
                PayrollAdjustment::find($allowance['allowance_id'])->update([
                    'amount' => $allowance['amount'],
                    'amount_type' => $allowance['amount_type'],
                    'adjustment_type_id' => $allowance['adjustment_type'],
                    'applicable_date' => $allowance['applicable_date'] . '-01'
                ]);
            } else {
                $ids[] = PayrollAdjustment::create([
                    'employee_id' => $employee_id,
                    'amount' => $allowance['amount'],
                    'amount_type' => $allowance['amount_type'],
                    'adjustment_type_id' => $allowance['adjustment_type'],
                    'applicable_date' => $allowance['applicable_date'] . '-01'
                ])->id;
            }
            PayrollAdjustment::allowance()->always()->where('employee_id', $employee_id)->whereNotIn('id', $ids)->delete();
        }
    }
}