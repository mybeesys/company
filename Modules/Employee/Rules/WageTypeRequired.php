<?php

namespace Modules\Employee\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WageTypeRequired implements ValidationRule
{
    protected $wage;

    public function __construct($wage)
    {
        $this->wage = $wage;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->wage !== '0' && is_null($value)) {
            $fail(__('employee::responses.wage_type_required'));
        }
    }
}
