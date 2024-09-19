<?php

use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/set-locale/{locale}', function ($locale) {
    session()->put('locale', $locale);
    return session()->get('locale');
})->name('set_locale');


// Route::post('/register', [AuthController::class, 'register'])->name('register');
// // Route::post('/login', [AuthController::class, 'login'])->name('login');

// Route::middleware('auth:api')->group(function () {
//     Route::post('logout', [AuthController::class, 'logout'])->name('logout');
//     Route::get('/user', [AuthController::class, 'user'])->name('user');
// });