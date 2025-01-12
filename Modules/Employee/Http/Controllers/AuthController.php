<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Employee\Models\Employee;
use Modules\Employee\Rules\EmailOrUserNameExists;

class AuthController extends Controller
{

    public function index()
    {        
        return view('employee::auth.login');
    }
    /**
     * Display a listing of the resource.
     */

    public function login(Request $request)
    {
        $attributes = $request->validate([
            'email' => ['required', 'string', new EmailOrUserNameExists],
            'password' => 'required'
        ]);
        $loginField = filter_var($attributes['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'user_name';

        if (!Auth::attempt([$loginField => $attributes['email'], 'password' => $attributes['password']])) {
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