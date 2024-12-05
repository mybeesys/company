<?php
namespace Modules\Employee\Services;

use Carbon\Carbon;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\PayrollAdjustment;


class AdjustmentAction
{
    public static function processPayrollAdjustment($adjustment_repeater, Employee $employee, string $date, $type)
    {
        if ($adjustment_repeater) {
            foreach ($adjustment_repeater as $adjustment) {
                if (isset($adjustment["{$type}_id"])) {
                    $ids[] = $adjustment["{$type}_id"];
                    PayrollAdjustment::where('id', $adjustment["{$type}_id"])->update([
                        'adjustment_type_id' => $adjustment['adjustment_type'],
                        'amount' => $adjustment['amount'],
                        'amount_type' => $adjustment['amount_type'],
                    ]);
                } else {
                    $id = PayrollAdjustment::create([
                        'employee_id' => $employee->id,
                        'adjustment_type_id' => $adjustment['adjustment_type'],
                        'amount' => $adjustment['amount'],
                        'amount_type' => $adjustment['amount_type'],
                        'apply_once' => true,
                        'applicable_date' => "{$date}-01",
                        'type' => $type
                    ])->id;
                    $ids[] = $id;
                }
            }
            if ($type == 'allowance') {
                $general_allowances = $employee->allowances()->always()->whereNotIn('id', $ids)->get();
                foreach ($general_allowances as $allowance) {
                    $allowance->update([
                        'applicable_date' => Carbon::createFromFormat('Y-m', $date)->addMonth()->startOfMonth(),
                    ]);
                }
                $employee->allowances()->once()->whereNotIn('id', $ids)->where('applicable_date', "{$date}-01")->delete();
            } else {
                $employee->deductions()->whereNotIn('id', $ids)->where('applicable_date', "{$date}-01")->once()->delete();
            }

        } else {
            if ($type == 'allowance') {
                $general_allowances = $employee->allowances()->always()->get();
                $employee->allowances()->where('applicable_date', "{$date}-01")->once()->delete();
                foreach ($general_allowances as $allowance) {
                    $allowance->update([
                        'applicable_date' => Carbon::createFromFormat('Y-m', $date)->addMonth()->startOfMonth(),
                    ]);
                }
            } else {
                $employee->deductions()->where('applicable_date', "{$date}-01")->once()->delete();
            }
        }
        return $ids;
    }
}