<?php

use Illuminate\Support\Facades\Route;
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
use Modules\Employee\Http\Middleware\EnureTimeSheetRulesExists;
use Modules\Employee\Models\DashboardRole;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Payroll;
use Modules\Employee\Models\PayrollAdjustment;
use Modules\Employee\Models\PayrollGroup;
use Modules\Employee\Models\PosRole;
use Modules\Employee\Models\Shift;
use Modules\Employee\Models\TimeCard;
use Modules\Employee\Models\TimeSheetRule;
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
require __DIR__ . '/auth.php';

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {


    Route::middleware(['auth'])->group(function () {

        Route::get('/', function () {
            return view('employee::layouts.master');
        })->name('dashboard');

        Route::controller(EmployeeController::class)->name('employees.')->prefix('employee')->group(function () {

            Route::get('', 'index')->name('index')->can('viewAny', Employee::class);
            Route::get('/create', 'create')->name('create')->can('create', Employee::class);
            Route::post('/store', 'store')->name('store')->can('create', Employee::class);
            Route::get('/{id}/edit', 'edit')->name('edit')->can('update', Employee::class);
            Route::patch('/{employee}', 'update')->name('update')->can('update', Employee::class);
            Route::get('/show/{id}', 'show')->name('show')->can('view', Employee::class);
            Route::delete('/{employee}', 'softDelete')->name('delete')->can('delete', Employee::class);
            Route::delete('/force-delete/{employee}', 'forceDelete')->name('forceDelete')->can('delete', Employee::class);
            Route::post('/restore/{employee}', 'restore')->name('restore')->can('update', Employee::class);

            Route::get('/{employee}/print', function (Employee $employee) {
                return view('employee::employee.print', compact('employee'))->render();
            })->can('print', Employee::class);

            Route::post('/create/validate', 'createLiveValidation')->name('create.validation');
            Route::post('/update/validate', 'updateLiveValidation')->name('update.validation');

            Route::get('/generate-pin', 'generatePin')->name('generate.pin')->can('create', Employee::class);
        });

        Route::controller(PermissionController::class)->name('permissions.')->prefix('permission')->group(function () {
            Route::patch('/{employee}/assign-pos-permissions', 'assignPosPermissionsToEmployee')->name('assign.employee')->can('update', PosRole::class);
            Route::get('/get-employee-pos-permissions/{id}', 'getEmployeePosPermissions')->can('view', PosRole::class);
            Route::patch('/{employee}/assign-dashboard-permissions', 'assignDashboardPermissionsToUser')->name('assign.user')->can('update', DashboardRole::class);
            Route::get('/get-employee-dashboard-permissions/{id}', 'getEmployeeDashboardPermissions')->can('view', DashboardRole::class);
        });


        Route::controller(PosRoleController::class)->name('roles.')->prefix('pos-role')->group(function () {
            Route::get('', 'index')->name('index')->can('viewAny', PosRole::class);
            Route::get('/show/{id}', 'show')->name('show')->can('view', PosRole::class);
            Route::get('/create', 'create')->name('create')->can('create', PosRole::class);
            Route::post('/store', 'store')->name('store')->can('create', PosRole::class);
            Route::get('/{id}/edit', 'edit')->name('edit')->can('update', PosRole::class);
            Route::patch('/{role}', 'update')->name('update')->can('update', PosRole::class);
            Route::delete('/{role}', 'destroy')->name('delete')->can('delete', PosRole::class);


            Route::post('/create/validate', 'createLiveValidation')->name('create.validation');
            Route::post('/update/validate', 'updateLiveValidation')->name('update.validation');
        });

        Route::controller(DashboardRoleController::class)->name('dashboard-roles.')->prefix('dashboard-role')->group(function () {
            Route::get('', 'index')->name('index')->can('viewAny', DashboardRole::class);
            Route::get('/create', 'create')->name('create')->can('create', DashboardRole::class);
            Route::get('/show/{dashboardRole}', 'show')->name('show')->can('view', DashboardRole::class);
            Route::post('/store', 'store')->name('store')->can('create', DashboardRole::class);
            Route::get('/{dashboardRole}/edit', 'edit')->name('edit')->can('update', DashboardRole::class);
            Route::patch('/{dashboardRole}', 'update')->name('update')->can('update', DashboardRole::class);
            Route::delete('/{dashboardRole}', 'destroy')->name('delete')->can('delete', DashboardRole::class);


            Route::post('/create/validate', 'createLiveValidation')->name('create.validation');
            Route::post('/update/validate', 'updateLiveValidation')->name('update.validation');
        });

        Route::controller(PayrollAdjustmentController::class)->name('adjustments.')->prefix('/adjustment')->group(function () {
            Route::get('', 'index')->name('index')->can('viewAny', PayrollAdjustment::class);

            Route::post('/store', 'store')->name('store')->can('create', PayrollAdjustment::class);

            Route::delete('/{adjustment}', 'destroy')->name('delete')->can('delete', PayrollAdjustment::class);

            Route::post('/store-payroll-allowance', 'storeAllowanceCache')->name('store-allowance-cache');
            Route::post('/store-payroll-deduction', 'storeDeductionCache')->name('store-deduction-cache');
        });

        Route::controller(PayrollAdjustmentTypeController::class)->name('adjustment_types.')->prefix('/adjustment-type')->group(function () {
            Route::get('', 'index')->name('index')->can('viewAny', PayrollAdjustment::class);

            Route::delete('/{adjustmentType}', 'destroy')->name('delete')->can('delete', PayrollAdjustment::class);

            Route::get('get-types', 'getAdjustmentsTypes')->name('get-types');
            Route::post('/store', 'store')->name('store');
            Route::post('/{adjustmentType}', 'update')->name('update');
        });

        Route::name('schedules.')->prefix('schedule')->group(function () {
            Route::controller(TimeSheetRuleController::class)->name('timesheet-rules.')->prefix('/timesheet-rule')->group(function () {
                Route::get('', 'index')->name('index')->can('viewAny', TimeSheetRule::class);
                Route::post('/store', 'store')->name('store')->can('update', TimeSheetRule::class);

                Route::post('/create/validate', 'createLiveValidation')->name('create.validation');
                Route::post('/update/validate', 'updateLiveValidation')->name('update.validation');
            });

            Route::controller(TimeCardController::class)->name('timecards.')->prefix('timecard')->group(function () {
                Route::get('', 'index')->name('index')->can('viewAny', TimeCard::class);
                Route::middleware([EnureTimeSheetRulesExists::class])->group(function () {
                    Route::get('/create', 'create')->name('create')->can('create', TimeCard::class);
                    Route::post('/store', 'store')->name('store')->can('create', TimeCard::class);
                    Route::get('/{timecard}/edit', 'edit')->name('edit')->can('update', TimeCard::class);
                    Route::patch('/{timecard}', 'update')->name('update')->can('update', TimeCard::class);

                });
                Route::delete('/{timecard}', 'destroy')->name('delete')->can('delete', TimeCard::class);

                Route::post('/create/validate', 'createLiveValidation')->name('create.validation');
            });

            Route::controller(ShiftController::class)->middleware([EnureTimeSheetRulesExists::class])->name('shifts.')->prefix('/shift')->group(function () {
                Route::get('', 'index')->name('index')->can('viewAny', Shift::class);
                Route::post('/store', 'store')->name('store')->can('create', Shift::class);

                Route::get('/get-shift', 'getShift')->name('getShift');

                Route::post('/copy-shifts', 'copy_shifts')->name('copy-shifts')->can('update', Shift::class);
            });

            Route::controller(PayrollGroupController::class)->middleware([EnureTimeSheetRulesExists::class])->name('payrolls-groups.')->prefix('/payroll-group')->group(function () {
                Route::get('', 'index')->name('index')->can('viewAny', PayrollGroup::class);
                Route::get('/create', 'create')->name('create')->can('create', PayrollGroup::class);
                Route::get('/{id}/edit', 'edit')->name('edit')->can('update', PayrollGroup::class);
                Route::delete('/{payrollGroup}', 'destroy')->name('delete')->can('delete', PayrollGroup::class);

                Route::post('/confirm/{payrollGroup}', 'confirmPayrollGroup')->name('confirm')->can('update', PayrollGroup::class);
            });

            Route::controller(PayrollController::class)->middleware([EnureTimeSheetRulesExists::class])->name('payrolls.')->prefix('/payroll')->group(function () {
                Route::get('', 'index')->name('index')->can('viewPayrolls');
                Route::get('/save', 'create')->name('create')->can('create', Payroll::class);
                Route::post('/store', 'store')->name('store')->can('create', Payroll::class);
                Route::get('/payroll-columns', 'getColumns')->name('get-columns');

                Route::post('/extend-lock', 'extendLock')->name('extendLock');

                Route::get('/print-all', 'printAll')->name('print-all');

                Route::get('/{payroll}/print', function (Payroll $payroll) {
                    return view('employee::schedules.payroll.print', compact('payroll'))->render();
                })->name('print')->can('print', Payroll::class);
    
            });

        });
    });

});

