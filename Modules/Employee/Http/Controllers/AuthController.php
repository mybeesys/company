<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\Employee;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function login(Request $request)
    {
        $attributes = $request->validate([
            'email' => 'required|email|exists:emp_employees,email', // exists:users,email exists is the opposite of unique
            'password' => 'required'
        ]);
        if (!auth()->attempt($attributes)) {
            return redirect()->back()->with('error', __('employee::responses.incorrect_credential'));
        }
        $user = Employee::firstWhere('email', $attributes['email']);

        if (!$user->ems_access) {
            auth()->logout();
            return to_route('login')->with('error', 'No permission to log in to dashboard');
        }
        session()->regenerate();

        return to_route('dashboard')->with('success', __('employee::responses.logged_in_successfully'));
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return to_route('login');
    }
}