<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Employee\Models\Employee;
use Modules\Employee\Rules\LastShiftEndStatus;

class StoreShiftRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $employee = Employee::find(request()->employee_id);
        $roleIds = $employee->roles->pluck('id')->toArray();
        $establishmentRoleIds = $employee->establishmentRoles->pluck('id')->toArray();
        return [
            'employee_id' => ['required', 'exists:employee_employees,id'],
            'date' => ['required', 'date'],
            'shift_repeater' => ['array', 'required', new LastShiftEndStatus()],
            'shift_repeater.*.startTime' => ['required', 'date_format:H:i'],
            'shift_repeater.*.endTime' => ['required', 'date_format:H:i'],
            'shift_repeater.*.end_status' => ['required', 'in:clockout,break'],
            'shift_repeater.*.role' => ['integer', 'exists:roles,id', Rule::in(array_merge($roleIds, $establishmentRoleIds))],
            'shift_id' => ['nullable', 'integer', 'exists:schedule_shifts,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $shifts = $this->input('shift_repeater', []);
            usort($shifts, function ($a, $b) {
                return $a['startTime'] <=> $b['startTime'];
            });

            foreach ($shifts as $index => $shift) {
                if (isset($shift['startTime'], $shift['endTime']) && $shift['startTime'] >= $shift['endTime']) {
                    $validator->errors()->add(
                        "shift_repeater.$index.endTime",
                        __('employee::general.startTime_before_endTime_error')
                    );
                }

                if (isset($shifts[$index + 1])) {
                    $nextShift = $shifts[$index + 1];
                    if ($shift['endTime'] > $nextShift['startTime']) {
                        $validator->errors()->add(
                            "shift_repeater.$index.endTime",
                            __('employee::general.time_overlap_error')
                        );
                        break;
                    }
                }
            }
        });
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
