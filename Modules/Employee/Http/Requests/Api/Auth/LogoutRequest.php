<?php

namespace Modules\Employee\Http\Requests\Api\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\Employee;

class LogoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'timecard_id' => ['required', 'exists:emp_time_cards,id'],
            'employee_id' => ['required', 'exists:emp_employees,id'],
            'clock_out_time' => ['required', 'date_format:Y-m-d H:i:s'],
        ];
    }

}
