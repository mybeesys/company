<?php

namespace Modules\Employee\Rules;

use Closure;
use DB;
use Illuminate\Contracts\Validation\ValidationRule;

class EmailOrUserNameExists implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = DB::table('emp_employees')
            ->where('email', $value)
            ->orWhere('user_name', $value)
            ->exists();
            if(!$exists){
                $fail(__('employee::responses.incorrect_credential'));
            }
    }
}
