<?php

namespace Modules\Employee\Services;

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
        $timecards = $employee->timecards()->whereBetween('date', [$monthStartDate, $monthEndDate]);

        $total_hours = $timecards->sum('hours_worked');
        $overtime_hours = $timecards->sum('overtime_hours');
        $regular_worked_hours = $total_hours - $overtime_hours;

        $total_worked_days = $timecards->get(['clock_in_time', 'clock_out_time'])->flatMap(function ($time) {
            return [Carbon::parse($time->clock_in_time)->format('Y-m-d'), Carbon::parse($time->clock_out_time)->format('Y-m-d')];
        })->unique()->filter()->count();

        $wage_due_before_tax = $this->calculateWageDueBeforeTax($employee, $basicWages, $timecards, $carbonMonth);
        $basic_wage = $basicWages->sum('rate');
        
        //apply working hours deduction
        if ($basic_wage > $wage_due_before_tax && request()->deduction_apply == "true") {
            $deduction_amount = $basic_wage - $wage_due_before_tax;
            $this->applyDeduction($employee->id, $deduction_amount, $monthStartDate, 'presence', 'الدوام');
        } else {
            $wage_due_before_tax = $basic_wage;
            $deduction_amount = 0;
        }
        [$allowances_html, $allowances_value] = $this->getAllowances($employee, $monthStartDate);
        [$deductions_html, $deductions_value] = $this->getDeductions($employee, $monthStartDate);
        $total_wage_before_tax = round($wage_due_before_tax + $allowances_value + $deduction_amount - $deductions_value, 2);

        return [
            'employee' => $this->getEmployeeName($employee),
            'establishments' => $this->getEstablishmentNames($employee),
            'regular_worked_hours' => $regular_worked_hours,
            'overtime_hours' => $overtime_hours,
            'total_hours' => $total_hours,
            'total_worked_days' => $total_worked_days,
            'basic_total_wage' => $basicWages->sum('rate'),
            'wage_due_before_tax' => $this->calculateWageDueBeforeTax($employee, $basicWages, $timecards, $carbonMonth),
            'allowances' => $allowances_html,
            'deductions' => $deductions_html,
            'total_wage_before_tax' => $total_wage_before_tax,
            'total_wage' => $total_wage_before_tax
        ];

    }

    public function applyDeduction($employee_id, $amount, $date, $type, $type_ar)
    {
        PayrollAdjustment::where('employee_id', $employee_id)->where('type', 'deduction')->where('applicable_date', $date)->delete();
        $type_id = PayrollAdjustmentType::where('name', $type_ar)->orWhere('name_en', $type)->first()?->id;
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
                'monthly' => $this->wageCalculationService->calculateMonthlyWage($timecards, $basicWage, $carbonMonth, $employee),
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

    private function getEstablishmentNames(Employee $employee)
    {
        return $employee->wageEstablishments()->pluck('name')->toArray();
    }

    private function getAllowances(Employee $employee, $date)
    {
        $allowances = $employee->allowances()->where(
            fn($query) =>
            $query->where('apply_once', false)->orWhereDate('applicable_date', $date)
        )->where('applicable_date', '<=', $date);
        $sum = $allowances->sum('amount');

        $html = "<div class='add-allowances-button d-flex flex-column text-nowrap' data-employee-id='$employee->id' data-employee-name='$employee->name' data-date='$date'";
        foreach ($allowances->get() as $key => $allowance) {
            $html .= "data-allowance-id-{$key}='$allowance->id' data-amount-{$key}='$allowance->amount' data-am-type-{$key}='$allowance->amount_type' data-allowance-type-{$key}='{$allowance->adjustmentType->id}'";
        }
        $html .= ">{$sum}</div>";
        return [$html, $sum];
    }

    private function getDeductions(Employee $employee, $date)
    {
        $deductions = $employee->deductions()->whereDate('applicable_date', $date);
        $sum = $deductions->sum('amount');
        $html = "<div class='add-deductions-button d-flex flex-column text-nowrap' data-employee-id='$employee->id' data-employee-name='$employee->name' data-date='$date'";
        foreach ($deductions->get() as $key => $deduction) {
            $html .= "data-deduction-id-{$key}='$deduction->id' data-amount-{$key}='$deduction->amount' data-am-type-{$key}='$deduction->amount_type' data-deduction-type-{$key}='{$deduction->adjustmentType->id}'";
        }
        $html .= ">{$sum}</div>";
        return [$html, $sum];
    }

}