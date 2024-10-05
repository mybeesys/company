<?php

use App\Http\Middleware\AuthenticateJWT;
use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\EmployeeController;
use Modules\Employee\Http\Controllers\MainController;
use Modules\Employee\Http\Controllers\RoleController;
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

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    AuthenticateJWT::class
])->group(function () {

    Route::controller(EmployeeController::class)->name('employees.')->prefix('employee')->group(function () {
        Route::get('employees/dashboard', [MainController::class, 'index'])->name('dashboard');

        Route::get('', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/{employee}/edit', 'edit')->name('edit');
        Route::patch('/{employee}', 'update')->name('update');
        Route::get('/show/{employee}', 'show')->name('show');
        Route::delete('/{employee}', 'softDelete')->name('delete');
        Route::delete('/force-delete/{employee}', 'forceDelete')->name('forceDelete');
        Route::post('/restore/{employee}', 'restore')->name('restore');

        Route::post('/create/validate', 'createLiveValidation')->name('create.validation');
        Route::post('/update/validate', 'updateLiveValidation')->name('update.validation');

        Route::get('/generate-pin', 'generatePin')->name('generate.pin');
    });

    Route::controller(RoleController::class)->name('roles.')->prefix('role')->group(function () {
        Route::get('', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/{role}/edit', 'edit')->name('edit');
        Route::patch('/{role}', 'update')->name('update');
        Route::delete('/{role}', 'destroy')->name('delete');


        Route::post('/create/validate', 'createLiveValidation')->name('create.validation');
        Route::post('/update/validate', 'updateLiveValidation')->name('update.validation');
    });

});

