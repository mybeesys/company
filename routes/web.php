<?php

use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;


Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/set-locale/{locale}', function ($locale) {
        session()->put('locale', $locale);
        return redirect()->back();
        
    })->name('set_locale');
});

// Route::post('/register', [AuthController::class, 'register'])->name('register');
// // Route::post('/login', [AuthController::class, 'login'])->name('login');

// Route::middleware('auth:api')->group(function () {
//     Route::post('logout', [AuthController::class, 'logout'])->name('logout');
//     Route::get('/user', [AuthController::class, 'user'])->name('user');
// });