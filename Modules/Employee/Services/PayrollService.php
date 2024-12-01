<?php

namespace Modules\Employee\Services;

use Cache;
use Carbon\Carbon;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\PayrollAdjustment;
use Modules\Employee\Models\PayrollAdjustmentType;

class PayrollService
{
    public function __construct(private WageCalculationService $wageCalculationService)
    {
    }

    public function calculateEmployeePayroll(Employee $employee, Carbon $carbonMonth, array $establishmentIds)
    {

        $monthStartDate = $carbonMonth->copy()->startOfMonth()->format('Y-m-d');
        $monthEndDate = $carbonMonth->copy()->endOfMonth()->format('Y-m-d');

        $basicWages = $employee->wages()->whereIn('establishment_id', $establishmentIds);
        $timecards = $employee->timecards()->whereBetween('date', [$monthStartDate, $monthEndDate])->whereIn('establishment_id', $establishmentIds);

        $total_hours = $timecards->sum('hours_worked');
        $overtime_hours = $timecards->sum('overtime_hours');
        $regular_worked_hours = $total_hours - $overtime_hours;

        $total_worked_days = $timecards->get(['clock_in_time', 'clock_out_time'])->flatMap(function ($time) {
            return [Carbon::parse($time->clock_in_time)->format('Y-m-d'), Carbon::parse($time->clock_out_time)->format('Y-m-d')];
        })->unique()->filter()->count();

        $wage_due_before_tax = round($this->calculateWageDueBeforeTax($employee, $basicWages, $timecards, $carbonMonth), 2);
        $basic_wage = $basicWages->sum('rate');

        //apply working hours deduction
        if ($basic_wage > $wage_due_before_tax && request()->deduction_apply == "true") {
            $deduction_amount = $basic_wage - $wage_due_before_tax;
            $this->applyDeduction($employee->id, $deduction_amount, $monthStartDate, 'presence', 'الدوام');
        } else {
            $wage_due_before_tax = $basic_wage;
            $deduction_amount = 0;
        }
        [$allowances_html, $allowances_value, $allowances_ids] = $this->getAllowances($employee, $monthStartDate);
        [$deductions_html, $deductions_value, $deductions_ids] = $this->getDeductions($employee, $monthStartDate);
        $total_wage_before_tax = round($wage_due_before_tax + $allowances_value + $deduction_amount - $deductions_value, 2);
        return [
            'employee' => $this->getEmployeeName($employee),
            'regular_worked_hours' => $regular_worked_hours,
            'overtime_hours' => $overtime_hours,
            'total_hours' => $total_hours,
            'total_worked_days' => $total_worked_days,
            'basic_total_wage' => $basicWages->sum('rate'),
            'wage_due_before_tax' => $wage_due_before_tax,
            'html_allowances' => $allowances_html,
            'html_deductions' => $deductions_html,
            'allowances' => $allowances_value,
            'deductions' => $deductions_value,
            'allowances_ids' => $allowances_ids,
            'deductions_ids' => $deductions_ids,
            'total_wage_before_tax' => $total_wage_before_tax,
            'total_wage' => $total_wage_before_tax
        ];

    }

    public function applyDeduction($employee_id, $amount, $date, $type, $type_ar)
    {
        $deduction = PayrollAdjustment::where('employee_id', $employee_id)->where('type', 'deduction')->where('applicable_date', $date)->get();
        $type_id = PayrollAdjustmentType::where('name', $type_ar)->orWhere('name_en', $type)->first()?->id;
        if ($deduction->isEmpty()) {
            if (!$type_id) {
                $type_id = PayrollAdjustmentType::create([
                    'name_en' => $type,
                    'name' => $type_ar,
                    'type' => 'deduction'
                ])->id;
            }
            PayrollAdjustment::create([
                'employee_id' => $employee_id,
                'amount' => $amount,
                'type' => 'deduction',
                'applicable_date' => $date,
                'apply_once' => true,
                'adjustment_type_id' => $type_id
            ]);
        }
    }

    public function fetchEmployees(array $employeeIds, array $establishmentIds)
    {
        return Employee::with(['allowances', 'timecards', 'wages', 'shifts', 'wageEstablishments'])
            ->whereIn('id', $employeeIds)
            ->whereHas('wageEstablishments', function ($query) use ($establishmentIds) {
                $query->whereIn('establishment_establishments.id', $establishmentIds);
            })
            ->get();
    }

    private function calculateWageDueBeforeTax(Employee $employee, $basicWages, $timecards, Carbon $carbonMonth)
    {
        $totalWage = 0;
        foreach ($basicWages->get() as $basicWage) {
            $totalWage += match ($basicWage->wage_type) {
                'monthly' => $this->wageCalculationService->calculateMonthlyWage($timecards->clone()->where('establishment_id', $basicWage->establishment_id), $basicWage, $carbonMonth, $employee),
                'fixed' => $basicWage->rate,
                default => 0,
            };
        }
        return $totalWage;
    }

    private function getEmployeeName(Employee $employee)
    {
        return session()->get('locale') === 'ar' ? $employee->name : $employee->name_en;
    }

    private function getAllowances(Employee $employee, $date)
    {
        $allowances = $employee->allowances()->where(
            fn($query) =>
            $query->where('apply_once', false)->orWhereDate('applicable_date', $date)
        )->where('applicable_date', '<=', $date);
        $sum = $allowances->sum('amount');

        $ids = $allowances->pluck('id')->toArray();

        $html = "<div class='add-allowances-button d-flex flex-column text-nowrap' data-employee-id='$employee->id' data-employee-name='$employee->name' data-date='$date'";

        $allowances_cache = collect(Cache::get("allowance_{$employee->id}_{$date}"));
        if ($allowances_cache) {
            foreach ($allowances_cache as $key => $allowance) {
                $html .= "data-deduction-id-{$key}='{$allowance['allowance_id']}' 
                data-amount-{$key}='{$allowance['amount']}' 
                data-am-type-{$key}='{$allowance['amount_type']}' 
                data-deduction-type-{$key}='{$allowance['adjustment_type']}'";
            }
        } else {
            foreach ($allowances->get() as $key => $allowance) {
                $html .= "data-allowance-id-{$key}='$allowance->id' data-amount-{$key}='$allowance->amount' data-am-type-{$key}='$allowance->amount_type' data-allowance-type-{$key}='{$allowance->adjustmentType->id}'";
            }
        }
        $html .= ">{$sum}</div>";
        return [$html, $sum, $ids];
    }

    private function getDeductions(Employee $employee, $date)
    {
        $deductions = $employee->deductions()->whereDate('applicable_date', $date);

        $deductions_cache = collect(Cache::get("deduction_{$employee->id}_{$date}"));

        $ids = $deductions->pluck('id')->toArray();
        $html = "<div class='add-deductions-button d-flex flex-column text-nowrap' data-employee-id='$employee->id' data-employee-name='$employee->name' data-date='$date'";

        if ($deductions_cache) {
            foreach ($deductions_cache as $key => $deduction) {
                $html .= "data-deduction-id-{$key}='{$deduction['deduction_id']}' 
                data-amount-{$key}='{$deduction['amount']}' 
                data-am-type-{$key}='{$deduction['amount_type']}' 
                data-deduction-type-{$key}='{$deduction['adjustment_type']}'";
            }
            $sum = $deductions_cache->sum('amount');
        } else {
            $sum = $deductions->sum('amount');
            foreach ($deductions->get() as $key => $deduction) {
                $html .= "data-deduction-id-{$key}='$deduction->id' data-amount-{$key}='$deduction->amount' data-am-type-{$key}='$deduction->amount_type' data-deduction-type-{$key}='{$deduction->adjustmentType->id}'";
            }
        }
        $html .= ">{$sum}</div>";
        return [$html, $sum, $ids];
    }

}