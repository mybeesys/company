<?php

use Illuminate\Support\Facades\Route;
use Modules\ClientsAndSuppliers\Http\Controllers\ClientController;
use Modules\ClientsAndSuppliers\Http\Controllers\ClientsAndSuppliersApiController;
use Modules\ClientsAndSuppliers\Http\Controllers\ClientsAndSuppliersController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'auth-central',
])->group(function () {
    Route::get('clients', [ClientsAndSuppliersApiController::class, 'clients'])->name('clients');
    Route::get('suppliers', [ClientsAndSuppliersApiController::class, 'suppliers'])->name('suppliers');
    Route::post('contact-save', [ClientsAndSuppliersApiController::class, 'store'])->name('contact-save');
    Route::get('contact-show/{id}', [ClientsAndSuppliersApiController::class, 'show'])->name('contact-show');
    Route::post('contact-update', [ClientsAndSuppliersApiController::class, 'update'])->name('contact-update');
    Route::get('contact-update-status/{id}', [ClientsAndSuppliersApiController::class, 'updateStatus'])->name('contact-update-status');
    Route::get('contact-destroy/{id}', [ClientsAndSuppliersApiController::class, 'destroy'])->name('contact-destroy');
});
