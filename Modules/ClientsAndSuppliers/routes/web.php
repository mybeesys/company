<?php

use App\Http\Middleware\AuthenticateJWT;
use Illuminate\Support\Facades\Route;
use Modules\ClientsAndSuppliers\Http\Controllers\ClientController;
use Modules\ClientsAndSuppliers\Http\Controllers\ClientsAndSuppliersController;
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

    // Route::middleware(AuthenticateJWT::class)->group(function () {
    Route::middleware(['auth'])->group(function () {
        Route::get('clients', [ClientController::class, 'index'])->name('clients');
        Route::get('suppliers', [ClientController::class, 'index'])->name('suppliers');
        Route::get('client-create', [ClientController::class, 'create'])->name('client-create');
        Route::get('supplier-create', [ClientController::class, 'create'])->name('supplier-create');
        Route::post('client-save', [ClientController::class, 'store'])->name('client-save');
        Route::get('client-show/{id}', [ClientController::class, 'show'])->name('client-show');
        Route::get('client-edit/{id}', [ClientController::class, 'edit'])->name('client-edit');
        Route::post('client-update', [ClientController::class, 'update'])->name('client-update');
        Route::get('client-update-status/{id}', [ClientsAndSuppliersController::class, 'updateStatus'])->name('client-update-status');
        Route::get('client-destroy/{id}', [ClientsAndSuppliersController::class, 'destroy'])->name('client-destroy');
    });
});
