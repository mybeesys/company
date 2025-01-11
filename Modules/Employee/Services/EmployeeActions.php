<?php

namespace Modules\Employee\Services;

use Carbon\Carbon;
use File;
use Modules\Employee\Models\PayrollAdjustment;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Wage;
use Modules\Employee\Notifications\EmployeeCreated;


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

            $uniqueRepeaterData = $repeaterData->groupBy($roleKey)->map(function ($group) use ($roleKey) {

                $mergedEstablishments = $group->pluck('establishment')->flatten()->unique()->values()->all();

                return [
                    $roleKey => $group->first()[$roleKey],
                    'establishment' => $mergedEstablishments
                ];
            })->values();

            $uniqueRepeaterData->each(function ($repData) use ($employee, $roleKey) {

                if (isset($repData['establishment']) && is_array($repData['establishment'])) {
                    foreach ($repData['establishment'] as $establishmentId) {
                        $employee->allRoles()->attach($repData[$roleKey], ['establishment_id' => $establishmentId]);
                    }
                } else {
                    $establishmentId = $repData['establishment'] ?? null;
                    $employee->allRoles()->attach($repData[$roleKey], ['establishment_id' => $establishmentId]);
                }
            });
        };

        if ($dashboardRepeaterData) {
            $processRepeaterData($dashboardRepeaterData, 'dashboardRole', $employee);
        }

        if ($PosRepeaterData) {
            $processRepeaterData($PosRepeaterData, 'posRole', $employee);
        }
    }


    public function assignEmployeeWage($wage_amount, $wage_type, $employee_id)
    {
        Wage::updateOrCreate(['employee_id' => $employee_id], [
            'rate' => $wage_amount,
            'wage_type' => $wage_type
        ]);
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
        $imageName = $this->request->has('image') ? $this->storeImage($this->request['image']) : null;

        $employee = Employee::create($this->request->merge([
            'image' => $imageName,
            'created_by' => auth()->user()->id,
            'employment_start_date' => Carbon::parse($this->request->get('employment_start_date'))->format('Y-m-d'),
            'employment_end_date' => $this->request->has('employment_end_date') ? Carbon::parse($this->request->get('employment_end_date'))->format('Y-m-d') : null
        ])->all());

        $this->assignRolesEstablishments($this->request->get('pos_role_repeater'), $this->request->get('dashboard_role_repeater'), $employee);

        $this->assignEmployeeWage($this->request->get('wage_amount'), $this->request->get('wage_type'), $employee->id);

        !empty($this->request->get('allowance_repeater')) && $this->storeUpdateEmployeeAllowances($this->request->get('allowance_repeater'), $employee->id);

        // auth()->user()->notify(new EmployeeCreated($employee));
    }

    public function update($employee)
    {
        $this->assignRolesEstablishments($this->request->get('pos_role_repeater'), $this->request->get('dashboard_role_repeater'), $employee);

        !empty($this->request->get('allowance_repeater')) ? $this->storeUpdateEmployeeAllowances($this->request->get('allowance_repeater'), $employee->id) : PayrollAdjustment::where('type', 'allowance')->where('employee_id', $employee->id)->delete();

        $this->assignEmployeeWage($this->request->get('wage_amount'), $this->request->get('wage_type'), $employee->id);

        $imageName = $this->request->has('image') ? $this->storeImage($this->request['image'], $employee->image) : null;

        $data = $this->request->merge([
            'employment_start_date' => Carbon::parse($this->request->get('employment_start_date'))->format('Y-m-d'),
            'employment_end_date' => $this->request->has('employment_end_date') ? Carbon::parse($this->request->get('employment_end_date'))->format('Y-m-d') : null,
        ]);

        if ($imageName) {
            $data = $data->merge([
                'image' => $imageName,
            ]);
        } else if (!$this->request->get('image_old')) {
            $data = $data->merge([
                'image' => null,
            ]);
        }
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