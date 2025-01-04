<?php

namespace Modules\Employee\Services;

use Cache;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Payroll;
use Modules\Employee\Models\PayrollGroup;

class PayrollAction
{
    public function storePayroll($employeeIds, $date, $request, $payroll_group_id)
    {
        $payroll_group = PayrollGroup::updateOrCreate(['id' => $payroll_group_id], [
            'name' => $request->validated('payroll_group_name'),
            'date' => $request->validated('date'),
            'state' => $request->validated('payroll_group_state'),
            'payment_status' => 'due',
            'gross_total' => 0
        ]);
        $payroll_group_id = $payroll_group->id;
        $gross_total = 0;
        $net_total = 0;
        $payroll_ids = [];
        foreach ($employeeIds as $employeeId) {
            $employee = Employee::firstWhere('id', $employeeId);
            $payrollData = Cache::get("payroll_table_{$date}_{$employeeId}");
            $allowance_key = "allowance_{$employeeId}_{$date}-01";
            $allowances_repeater = Cache::get($allowance_key);
            Cache::forget($allowance_key);

            $deduction_key = "deduction_{$employeeId}_{$date}-01";
            $deductions_repeater = Cache::get($deduction_key);

            Cache::forget($deduction_key);

            if ($allowances_repeater) {
                $allowances_ids = AdjustmentAction::processPayrollAdjustment($allowances_repeater, $employee, $date, 'allowance');
            }
            if ($deductions_repeater) {
                $deductions_ids = AdjustmentAction::processPayrollAdjustment($deductions_repeater, $employee, $date, 'deduction');
            }

            $payroll = Payroll::updateOrCreate(['employee_id' => $employeeId, 'payroll_group_id' => $payroll_group_id, 'establishment_id' => $payrollData['establishment_id']], [
                'regular_worked_hours' => $payrollData['regular_worked_hours'],
                'overtime_hours' => $payrollData['overtime_hours'],
                'total_hours' => $payrollData['total_hours'],
                'total_worked_days' => $payrollData['total_worked_days'],
                'basic_total_wage' => $payrollData['basic_total_wage'],
                'wage_due_before_tax' => $payrollData['wage_due_before_tax'],
                'allowances' => $payrollData['allowances'],
                'deductions' => $payrollData['deductions'],
                // 'total_wage_before_tax' => $payrollData['total_wage'],
                'total_wage' => $payrollData['total_wage'],
            ]);
            // $net_total += $payrollData['total_wage'];
            $gross_total += $payrollData['total_wage'];
            $payroll_ids[] = $payroll->id;
        }
        Payroll::where('payroll_group_id', $payroll_group_id)->whereNotIn('id', $payroll_ids)->delete();
        $payroll_group->update([
            // 'net_total' => $net_total,
            'gross_total' => $gross_total
        ]);

        $payroll->adjustments()->sync(array_merge($allowances_ids ?? [], $deductions_ids ?? []));

        collect($employeeIds)->each(function ($employeeId) use ($date) {
            Cache::forget("payroll_table_{$date}" . $employeeId);
        });
    }
}