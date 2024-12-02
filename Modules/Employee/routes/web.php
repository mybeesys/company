<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\LoginController;
use Modules\Employee\Http\Controllers\PayrollAdjustmentController;
use Modules\Employee\Http\Controllers\PayrollAdjustmentTypeController;
use Modules\Employee\Http\Controllers\DashboardRoleController;
use Modules\Employee\Http\Controllers\EmployeeController;
use Modules\Employee\Http\Controllers\PayrollController;
use Modules\Employee\Http\Controllers\PayrollGroupController;
use Modules\Employee\Http\Controllers\PermissionController;
use Modules\Employee\Http\Controllers\PosRoleController;
use Modules\Employee\Http\Controllers\ShiftController;
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
])->group(function () {

    Route::middleware(['guest'])->group(function () {

        Route::get('/login', function () {
            return view('employee::auth.login');
        })->name('login');

        Route::post('/postlogin', [LoginController::class, 'login'])->name('login.postLogin');
    });


    
    Route::middleware(['auth'])->group(function () {
        
        Route::get('logout', [LoginController::class, 'logout'])->name('logout');
        
        Route::get('/', function () {
            return view('employee::layouts.master');
        })->name('dashboard');

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
            Route::patch('/{employee}/assign-pos-permissions', 'assignPosPermissionsToEmployee')->name('assign.employee');
            Route::get('/get-employee-pos-permissions/{id}', 'getEmployeePosPermissions');
            Route::patch('/{employee}/assign-dashboard-permissions', 'assignDashboardPermissionsToUser')->name('assign.user');
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

        Route::controller(PayrollAdjustmentTypeController::class)->name('adjustment_types.')->prefix('/allowance-type')->group(function () {
            Route::post('/store', 'store')->name('store');
        });

        Route::name('schedules.')->prefix('schedule')->group(function () {
            Route::controller(TimeSheetRuleController::class)->name('timesheet-rules.')->prefix('/timesheet-rule')->group(function () {
                Route::get('', 'index')->name('index');
                Route::post('/store', 'store')->name('store');

                Route::post('/create/validate', 'createLiveValidation')->name('create.validation');
                Route::post('/update/validate', 'updateLiveValidation')->name('update.validation');
            });

            Route::controller(TimeCardController::class)->name('timecards.')->prefix('timecard')->group(function () {
                Route::get('', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/store', 'store')->name('store');
                Route::get('/{timecard}/edit', 'edit')->name('edit');
                Route::patch('/{timecard}', 'update')->name('update');
                Route::delete('/{timecard}', 'destroy')->name('delete');
                Route::post('/create/validate', 'createLiveValidation')->name('create.validation');
            });

            Route::controller(ShiftController::class)->name('shifts.')->prefix('/shift')->group(function () {
                Route::get('', 'index')->name('index');
                Route::post('/store', 'store')->name('store');

                Route::get('/get-shift', 'getShift')->name('getShift');

                Route::post('/copy-shifts', 'copy_shifts')->name('copy-shifts');
            });

            Route::controller(PayrollGroupController::class)->name('payrolls-groups.')->prefix('/payroll-group')->group(function () {
                Route::get('', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::get('/{id}/edit', 'edit')->name('edit');
            });

            Route::controller(PayrollController::class)->name('payrolls.')->prefix('/payroll')->group(function () {
                Route::get('', 'index')->name('index');
                Route::get('/save', 'create')->name('create');
                Route::post('/store', 'store')->name('store');

                Route::post('/extend-lock', 'extendLock')->name('extendLock');
            });

            Route::controller(PayrollAdjustmentController::class)->name('adjustments.')->prefix('/adjustment')->group(function () {
                Route::post('/store-payroll-allowance', 'storeAllowance')->name('store-payroll-allowance');
                Route::post('/store-payroll-deduction', 'storeDeduction')->name('store-payroll-deduction');
            });
        });
    });

});

