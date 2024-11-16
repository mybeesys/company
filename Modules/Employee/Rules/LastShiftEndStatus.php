<?php

namespace Modules\Employee\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LastShiftEndStatus implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $lastEndStatus = $value[array_key_last($value)]['end_status'] ?? null;
        if ($lastEndStatus === 'break') {
            $fail(__('The last shift end status cannot be "break".'));
        }
    }
}
