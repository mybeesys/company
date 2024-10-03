<?php

use App\Http\Middleware\AuthenticateJWT;
use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\EmployeeController;
use Modules\Employee\Http\Controllers\MainController;
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
])->name('employees.')->group(function () {
    Route::get('employees/dashboard', [MainController::class, 'index'])->name('dashboard');

    Route::get('employees', [EmployeeController::class,'index'])->name('index');
    Route::get('employee/create', [EmployeeController::class,'create'])->name('create');
    Route::post('employee/store', [EmployeeController::class,'store'])->name('store');
    Route::get('employee/{employee}/edit', [EmployeeController::class,'edit'])->name('edit');
    Route::put('employee/{employee}', [EmployeeController::class,'update'])->name('update');
    // Route::get('employee/{employee}', [EmployeeController::class,'show'])->name('show');
    Route::delete('employee/{employee}', [EmployeeController::class,'softDelete'])->name('delete');
    Route::delete('employee/force-delete/{employee}', [EmployeeController::class,'forceDelete'])->name('forceDelete');
    Route::post('employee/restore/{employee}', [EmployeeController::class,'restore'])->name('restore');

    Route::post('employee/create/validate', [EmployeeController::class,'createLiveValidation'])->name('create.validation');
    Route::post('employee/update/validate', [EmployeeController::class,'updateLiveValidation'])->name('update.validation');

    Route::get('employee/generate-pin', [EmployeeController::class,'generatePin'])->name('generate.pin');
});

