<?php

use App\Http\Middleware\LocalizationMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\UserManagement\Http\Controllers\UserManagementController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware' => [LocalizationMiddleware::class]], function () {

    Route::get('/', function () {
        return view('usermanagement::index');
    })->name('dashboard');


    Route::middleware([
        'web',
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,

    ])->group(function () {
        Route::get('/default', function () {
            dd(\App\Models\User::all());
            return 'This is your multi-tenant application. The id of the current tenant is ' . tenant('id');
        });

        Route::get('/login', function () {
            return view('usermanagement::login');
        });

        Route::post('/postlogin', [UserManagementController::class, 'login'])->name('login.postLogin');


        Route::resource('usermanagement', UserManagementController::class)->names('usermanagement');
    });
});
