<?php

use Illuminate\Support\Facades\Route;
use Modules\Franchise\Http\Controllers\FranchiseBranchController;
use Modules\Franchise\Http\Controllers\FranchiseController;
use Modules\Franchise\Http\Controllers\FranchiseCompanyController;
use Modules\Franchise\Http\Controllers\FranchiseContractController;
use Modules\Franchise\Http\Controllers\FranchiseCustomMenuController;
use Modules\Franchise\Http\Controllers\FranchiseProductsController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;


Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {


    Route::prefix('franchise')->name('franchise.')->group(function () {

        Route::get('branches', [FranchiseBranchController::class, 'index'])->name('branches.index');
        Route::post('branches', [FranchiseBranchController::class, 'store'])->name('branches.store');
        Route::get('branches/{id}/edit', [FranchiseBranchController::class, 'edit'])->name('branches.edit'); // كان index وغيرناه لـ edit
        Route::put('branches/{id}', [FranchiseBranchController::class, 'update'])->name('branches.update'); // تغيير لـ PUT
        Route::delete('branches/{id}', [FranchiseBranchController::class, 'destroy'])->name('branches.destroy');

        Route::get('products-management', [FranchiseProductsController::class, 'index'])->name('products.index');
        Route::get('products/permissions/{id}', [FranchiseProductsController::class, 'getFranchiseProducts'])->name('products.permissions');
        Route::post('products/update', [FranchiseProductsController::class, 'update'])->name('products.update');

        Route::prefix('/custom-menus')->group(function () {
            Route::get('/', [FranchiseCustomMenuController::class, 'index'])->name('franchise.custom_menus.index');
            Route::get('/get-data/{id}', [FranchiseCustomMenuController::class, 'getFranchiseData']);
            Route::post('/update', [FranchiseCustomMenuController::class, 'update'])->name('custom_menus.update');
        });

        Route::resource('companies', FranchiseCompanyController::class);

        Route::prefix('contracts')->name('contracts.')->group(function () {
            Route::post('/store', [FranchiseContractController::class, 'store'])->name('store');
            Route::delete('/{id}', [FranchiseContractController::class, 'destroy'])->name('destroy');

            Route::get('/{id}/edit', [FranchiseContractController::class, 'edit'])->name('edit');
            Route::put('/{id}', [FranchiseContractController::class, 'update'])->name('update');
        });
    });
});
