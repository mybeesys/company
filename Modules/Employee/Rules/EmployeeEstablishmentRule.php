<?php

namespace Modules\Employee\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Establishment\Models\Establishment;

class EmployeeEstablishmentRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== 'all' && ! Establishment::where('id', $value)->exists()) {
            $fail(__('employee::responses.employee_establishment_rule_fail'));
        }
    }
}
