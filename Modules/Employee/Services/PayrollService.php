<?php

namespace Modules\Employee\Services;

use Cache;
use Carbon\Carbon;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\PayrollAdjustmentType;
use Modules\Establishment\Models\Establishment;

class PayrollService
{
    public function __construct(private WageCalculationService $wageCalculationService)
    {
    }

    public function calculateEmployeePayroll(Employee $employee, Carbon $carbonMonth)
    {
        $establishment_id = $employee->establishment_id;
        $establishments = Establishment::active()->notMain()->get(['id', 'name']);

        $monthStartDate = $carbonMonth->copy()->startOfMonth()->format('Y-m-d');
        $monthEndDate = $carbonMonth->copy()->endOfMonth()->format('Y-m-d');

        $basicWage = $employee->wage;

        $timecards = $employee->timecards()->whereNotIn('date', $this->wageCalculationService->timeSheetRuleService->getOffDaysDates($carbonMonth))->whereBetween('date', [$monthStartDate, $monthEndDate])->where('establishment_id', $establishment_id);
        $total_hours = $timecards->sum('hours_worked');
        $overtime_hours = $timecards->sum('overtime_hours');
        $regular_worked_hours = $total_hours - $overtime_hours;

        $total_worked_days = $timecards->get(['clock_in_time', 'clock_out_time'])->flatMap(function ($time) {
            return [Carbon::parse($time->clock_in_time)->format('Y-m-d'), Carbon::parse($time->clock_out_time)->format('Y-m-d')];
        })->unique()->filter()->count();

        $wage_due_before_tax = round($this->calculateWageDue($employee, $basicWage, $timecards, $carbonMonth), 2);

        [$allowances_array, $total_allowances, $allowances_value, $allowances_ids] = $this->getAllowances($employee, $monthStartDate);
        [$deductions_array, $total_deductions, $deductions_value, $deductions_ids] = $this->getDeductions($employee, $monthStartDate);

        $total_wage_before_tax = round($wage_due_before_tax + $allowances_value - $deductions_value, 2);
        return [
            'employee' => $this->getEmployeeName($employee),
            'establishment_id' => $establishment_id,
            'establishment' => $this->getEstablishmentHtml($employee, $establishments, $establishment_id),
            'regular_worked_hours' => round($regular_worked_hours, 2),
            'overtime_hours' => $overtime_hours,
            'total_hours' => $total_hours,
            'total_worked_days' => $total_worked_days,
            'basic_total_wage' => $basicWage?->rate ?? 0,
            'wage_due_before_tax' => $wage_due_before_tax,
            'allowances_array' => $allowances_array,
            'total_allowances' => $total_allowances,
            'deductions_array' => $deductions_array,
            'total_deductions' => $total_deductions,
            'allowances' => $allowances_value,
            'deductions' => $deductions_value,
            'allowances_ids' => $allowances_ids,
            'deductions_ids' => $deductions_ids,
            // 'total_wage_before_tax' => $total_wage_before_tax,
            'total_wage' => $total_wage_before_tax
        ];

    }

    public function fetchEmployees(array $employeeIds, array $establishmentIds)
    {
        return Employee::with(['allowances', 'deductions', 'timecards', 'wage', 'shifts', 'defaultEstablishment'])
            ->whereIn('id', $employeeIds)
            ->whereIn('establishment_id', $establishmentIds)
            ->whereHas('wage')
            ->get();
    }

    private function calculateWageDue(Employee $employee, $basicWage, $timecards, Carbon $carbonMonth)
    {
        $totalWage = 0;
        $totalWage += match ($basicWage?->wage_type) {
            'variable' => $this->wageCalculationService->calculateMonthlyWage($timecards, $basicWage, $carbonMonth, $employee),
            'fixed' => $basicWage->rate,
            default => 0,
        };
        return $totalWage;
    }

    private function getEmployeeName(Employee $employee)
    {
        return $employee->{get_name_by_lang()};
    }

    private function getAllowances(Employee $employee, $date)
    {

        $allowances = $employee->allowances()->where(
            fn($query) =>
            $query->where('apply_once', false)->orWhereDate('applicable_date', $date)
        )->where('applicable_date', '<=', $date);
        $ids = $allowances->pluck('id')->toArray();

        $allowances_array = [];

        $allowances_cache = collect(Cache::get("allowance_{$employee->id}_{$date}"));
        $common_html = "<div class='add-allowances-button d-flex gap-3 align-items-center text-nowrap' data-employee-id='$employee->id' data-employee-name='$employee->name' data-date='$date'";

        if ($allowances_cache->isNotEmpty() && (request()->firstEnter === "false")) {
            [$allowances_array, $common_html] = $this->generateAdjustmentDiv($allowances_cache, $common_html, "allowance");

            $sum = $allowances_cache->sum('amount');
            $common_html .= ">{$sum}
                <i class='ki-duotone ki-plus-square fs-4'>
                    <span class='path1'></span>
                    <span class='path2'></span>
                    <span class='path3'></span>
                </i>
            </div>";
        } else {
            $allowances = $allowances->get(['amount_type', 'adjustment_type_id', 'amount', 'id']);

            foreach ($allowances as &$allowance) {
                $allowance['adjustment_type'] = $allowance['adjustment_type_id'];
                $adjustment_type_name = PayrollAdjustmentType::find($allowance['adjustment_type'])?->translatedName;
                $allowance['adjustment_type_name'] = $adjustment_type_name;
            }
            Cache::forever("allowance_{$employee->id}_{$date}", $allowances);
            [$allowances_array, $common_html] = $this->generateAdjustmentDiv($allowances, $common_html, "allowance");

            $sum = $allowances?->sum('amount') ?? 0;
            $common_html .= ">{$sum}
            <i class='ki-duotone ki-plus-square fs-4'>
                <span class='path1'></span>
                <span class='path2'></span>
                <span class='path3'></span>
            </i>
            </div>";
        }
        return [$allowances_array, $common_html, $sum, $ids];
    }

    private function getDeductions(Employee $employee, $date)
    {
        $deductions = $employee->deductions()->whereDate('applicable_date', $date);

        $deductions_array = [];

        $deductions_cache = collect(Cache::get("deduction_{$employee->id}_{$date}"));
        $ids = $deductions->pluck('id')->toArray();
        $common_html = "<div class='add-deductions-button d-flex gap-3 align-items-center text-nowrap' data-employee-id='$employee->id' data-employee-name='$employee->name' data-date='$date'";

        if ($deductions_cache->isNotEmpty() && (request()->firstEnter === "false")) {
            [$deductions_array, $common_html] = $this->generateAdjustmentDiv($deductions_cache, $common_html, "deduction");

            $sum = $deductions_cache->sum('amount');
            $common_html .= ">{$sum}
                <i class='ki-duotone ki-plus-square fs-4'>
                    <span class='path1'></span>
                    <span class='path2'></span>
                    <span class='path3'></span>
                </i>
            </div>";

        } else {
            $deductions = $deductions->get(['amount_type', 'adjustment_type_id', 'amount', 'id']);

            foreach ($deductions as &$deduction) {
                $deduction['adjustment_type'] = $deduction['adjustment_type_id'];
                $adjustment_type_name = PayrollAdjustmentType::find($deduction['adjustment_type'])?->name;
                $deduction['adjustment_type_name'] = $adjustment_type_name;
            }

            Cache::forever("deduction_{$employee->id}_{$date}", $deductions);
            [$deductions_array, $common_html] = $this->generateAdjustmentDiv($deductions, $common_html, "deduction");

            $sum = $deductions?->sum('amount') ?? 0;
            $common_html .= ">{$sum}
                <i class='ki-duotone ki-plus-square fs-4'>
                    <span class='path1'></span>
                    <span class='path2'></span>
                    <span class='path3'></span>
                </i>
            </div>";
        }
        return [$deductions_array, $common_html, $sum, $ids];
    }

    public function generateAdjustmentDiv($adjustments, $common_html, $type)
    {
        $adjustments_array = [];

        $adjustments_types = PayrollAdjustmentType::whereHas($type . 's')->get()
            ->unique(function ($item) {
                return $item->{get_name_by_lang()} ?? $item->name ?? $item->name_en;
            })->toArray();

        foreach ($adjustments as $key => $adjustment) {
            $element = " data-$type-id-{$key}='{$adjustment['id']}' 
                        data-amount-{$key}='{$adjustment['amount']}' 
                        data-am-type-{$key}='{$adjustment['amount_type']}' 
                        data-$type-type-{$key}='{$adjustment['adjustment_type']}'";

            $common_html .= "{$element}";

            // Sum amounts for the same adjustment_type_name
            if (!isset($adjustments_array[$adjustment['adjustment_type_name']])) {
                $adjustments_array[$adjustment['adjustment_type_name']] = 0;
            }
            $adjustments_array[$adjustment['adjustment_type_name']] += $adjustment['amount'];
        }

        // Convert summed amounts into HTML divs
        $name = $type[get_name_by_lang()] ?? "name" ?? "name_en";
        foreach ($adjustments_types as $type) {
            $adjustments_array[$type[$name]] = "<div>" . (array_key_exists($type[$name], $adjustments_array) ? $adjustments_array[$type[$name]] : 0 ?? 0) . "</div>";
        }

        return [$adjustments_array, $common_html];
    }


    private function getEstablishmentHtml($employee, $establishments, $selected)
    {
        $html = "<select class='form-select d-flex' data-employee-id={$employee->id} name='employee_establishment_{$employee->id}'>";
        foreach ($establishments as $establishment) {
            $html .= "<option value='{$establishment->id}'";
            if ($establishment->id == $selected) {
                $html .= " selected";
            }
            $html .= ">{$establishment->name}</option>";
        }
        $html .= "</select>";

        return $html;
    }
}