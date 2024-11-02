<?php

use App\Http\Middleware\AuthenticateJWT;
use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\DashboardRoleController;
use Modules\Employee\Http\Controllers\EmployeeController;
use Modules\Employee\Http\Controllers\MainController;
use Modules\Employee\Http\Controllers\PermissionController;
use Modules\Employee\Http\Controllers\PosRoleController;
use Modules\Employee\Http\Controllers\TimeCardController;
use Modules\Employee\Http\Controllers\TimeSheetRuleController;
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
    Route::controller(TimeCardController::class)->name('timecards.')->prefix('timecard')->group(function () {
        Route::get('', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/{timecard}/edit', 'edit')->name('edit');
        Route::patch('/{timecard}', 'update')->name('update');
        Route::delete('/{timecard}', 'destroy')->name('delete');
        Route::post('/create/validate', 'createLiveValidation')->name('create.validation');
    });

    Route::controller(EmployeeController::class)->name('employees.')->prefix('employee')->group(function () {

        Route::get('', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::patch('/{employee}', 'update')->name('update');
        Route::get('/show/{id}', 'show')->name('show');
        Route::delete('/{employee}', 'softDelete')->name('delete');
        Route::delete('/force-delete/{employee}', 'forceDelete')->name('forceDelete');
        Route::post('/restore/{employee}', 'restore')->name('restore');

        Route::post('/create/validate', 'createLiveValidation')->name('create.validation');
        Route::post('/update/validate', 'updateLiveValidation')->name('update.validation');

        Route::get('/generate-pin', 'generatePin')->name('generate.pin');
    });

    Route::controller(PermissionController::class)->name('permissions.')->prefix('permission')->group(function () {
        Route::patch('/{employee}/assign-pos-permissions', 'aasignPosPermissionsToEmployee')->name('assign.employee');
        Route::get('/get-employee-pos-permissions/{id}', 'getEmployeePosPermissions');
        Route::patch('/{employee}/assign-dashboard-permissions', 'aasignDashboardPermissionsToUser')->name('assign.user');
        Route::get('/get-employee-dashboard-permissions/{id}', 'getEmployeeDashboardPermissions');
    });


    Route::controller(PosRoleController::class)->name('roles.')->prefix('pos-role')->group(function () {
        Route::get('', 'index')->name('index');
        Route::get('/show/{id}', 'show')->name('show');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::patch('/{role}', 'update')->name('update');
        Route::delete('/{role}', 'destroy')->name('delete');


        Route::post('/create/validate', 'createLiveValidation')->name('create.validation');
        Route::post('/update/validate', 'updateLiveValidation')->name('update.validation');
    });


    Route::controller(DashboardRoleController::class)->name('dashboard-roles.')->prefix('dashboard-role')->group(function () {
        Route::get('', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::get('/show/{dashboardRole}', 'show')->name('show');
        Route::post('/store', 'store')->name('store');
        Route::get('/{dashboardRole}/edit', 'edit')->name('edit');
        Route::patch('/{dashboardRole}', 'update')->name('update');
        Route::delete('/{dashboardRole}', 'destroy')->name('delete');


        Route::post('/create/validate', 'createLiveValidation')->name('create.validation');
        Route::post('/update/validate', 'updateLiveValidation')->name('update.validation');
    });
    Route::name('schedules.')->prefix('schedule')->group(function () {
        Route::controller(TimeSheetRuleController::class)->name('timesheet-rules.')->prefix('/timesheet-rule')->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::get('/show/{dashboardRole}', 'show')->name('show');
            Route::post('/store', 'store')->name('store');
            Route::get('/{dashboardRole}/edit', 'edit')->name('edit');
            Route::patch('/{dashboardRole}', 'update')->name('update');
            Route::delete('/{dashboardRole}', 'destroy')->name('delete');


            Route::post('/create/validate', 'createLiveValidation')->name('create.validation');
            Route::post('/update/validate', 'updateLiveValidation')->name('update.validation');
        });
    });
});

