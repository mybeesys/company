<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Modules\Employee\Models\Employee;

class PasswordResetController extends Controller
{
    public function forgotPasswordForm()
    {
        return view('employee::auth.forgot-password');
    }

    /**
     * Accept email or username (same as login). Only employees with dashboard access
     * and a non-empty email receive a reset link (Laravel broker uses email as token key).
     */
    public function sendResetLink(Request $request)
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:100'],
        ]);

        $login = $validated['login'];

        $employee = Employee::query()
            ->where('ems_access', true)
            ->where(function ($q) use ($login) {
                $q->where('email', $login)
                    ->orWhere('user_name', $login);
            })
            ->first();

        if ($employee && filled($employee->email)) {
            $status = Password::broker('employees')->sendResetLink(
                ['email' => $employee->email]
            );

            if ($status === Password::RESET_THROTTLED) {
                return back()
                    ->withInput()
                    ->with('error', __('employee::passwords.throttled'));
            }
        }

        return back()->withInput()->with('success', __('employee::passwords.sent'));
    }

    public function resetPasswordForm(Request $request, string $token)
    {
        return view('employee::auth.reset-password', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::broker('employees')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Employee $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return to_route('login')->with('success', __('employee::passwords.reset'));
        }

        $key = str_replace('passwords.', '', $status);

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __("employee::passwords.$key")]);
    }
}
