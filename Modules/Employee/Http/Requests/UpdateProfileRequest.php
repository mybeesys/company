<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Employee\Models\Employee;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => trim((string) $this->input('email', '')),
            'user_name' => $this->filled('user_name')
                ? trim((string) $this->input('user_name'))
                : null,
            'phone_number' => $this->filled('phone_number')
                ? trim((string) $this->input('phone_number'))
                : null,
        ]);
    }

    public function rules(): array
    {
        /** @var Employee $user */
        $user = $this->user();

        $emailRules = ['required', 'email'];
        if ($this->input('email') !== $user->email) {
            $emailRules[] = Rule::unique(Employee::class, 'email')->ignore($user);
        }

        $userNameRules = ['nullable', 'string', 'max:50'];
        if ($this->input('user_name') !== $user->user_name) {
            $userNameRules[] = Rule::unique(Employee::class, 'user_name')->ignore($user);
        }

        return [
            'name' => ['required', 'string', 'max:50'],
            'name_en' => ['required', 'string', 'max:50'],
            'email' => $emailRules,
            'phone_number' => ['nullable', 'digits_between:10,15'],
            'user_name' => $userNameRules,
            'image' => ['nullable', 'image', 'max:3072'],
            'image_old' => ['nullable', 'boolean'],
            'current_password' => ['required_with:password', 'nullable', 'current_password:web'],
            'password' => ['nullable', 'confirmed', Password::default()],
        ];
    }
}
