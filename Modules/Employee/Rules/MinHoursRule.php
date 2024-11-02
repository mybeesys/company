<?php

namespace Modules\Employee\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MinHoursRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ((int) explode(':', $value)[1] >= 60){
            $fail(__('employee::responses.minutes_cannot_be_above_60'));
        }
    }
}
