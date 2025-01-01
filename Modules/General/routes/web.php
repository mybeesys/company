<?php

use Illuminate\Support\Facades\Route;
use Modules\General\Http\Controllers\GeneralController;
use Modules\General\Http\Controllers\TaxController;
use Modules\General\Http\Controllers\TransactionController;
use Modules\Sales\Http\Controllers\SellController;
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

    Route::middleware(['auth'])->group(function () {

        Route::get('taxes', [TaxController::class, 'index'])->name('taxes');
        Route::post('store-tax', [TaxController::class, 'store'])->name('store-tax');
        Route::post('update-tax', [TaxController::class, 'update'])->name('update-tax');
        Route::get('delete-tax/{id}', [TaxController::class, 'destroy'])->name('delete-tax');



        Route::get('transaction-show/{id}', [TransactionController::class, 'show'])->name('transaction-show');


        Route::get('general-setting', [GeneralController::class, 'setting'])->name('general-setting');


    });
});