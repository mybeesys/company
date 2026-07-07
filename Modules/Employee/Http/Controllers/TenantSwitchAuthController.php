<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\TenantSwitchToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Employee\Models\Employee;

class TenantSwitchAuthController extends Controller
{
    public function __invoke(Request $request)
    {
        $token = (string) $request->query('token', '');
        $tenantId = (string) tenant('id');

        if ($token === '' || $tenantId === '') {
            return redirect()->route('login')->with('error', __('employee::my_companies.switch_failed'));
        }

        try {
            $payload = TenantSwitchToken::verify($token, $tenantId);
        } catch (\Throwable) {
            return redirect()->route('login')->with('error', __('employee::my_companies.switch_failed'));
        }

        $employee = Employee::query()
            ->where('email', $payload['email'])
            ->where('ems_access', true)
            ->first();

        if ($employee === null) {
            return redirect()->route('login')->with('error', __('employee::my_companies.switch_no_employee'));
        }

        Auth::login($employee, true);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', __('employee::my_companies.switch_success'));
    }
}
