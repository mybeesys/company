<?php

use Illuminate\Support\Facades\Route;
use Modules\Franchise\Http\Controllers\FranchiseBranchController;
use Modules\Franchise\Http\Controllers\FranchiseCompanyController;
use Modules\Franchise\Http\Controllers\FranchiseContractController;
use Modules\Franchise\Http\Controllers\FranchiseCustomMenuController;
use Modules\Franchise\Http\Controllers\FranchiseProductsController;
use Modules\Franchise\Support\FranchisePermissions;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    Route::middleware(['auth'])->prefix('franchise')->name('franchise.')->group(function () {
        $perm = fn (string ...$names) => 'dashboard.perm:'.implode(',', $names);

        Route::post('contracts/{id}/extend', [FranchiseContractController::class, 'extend'])
            ->middleware($perm(...FranchisePermissions::for('Companies', 'update')))
            ->name('franchise.contracts.extend');
        Route::get('contracts/{id}/extension-history', [FranchiseContractController::class, 'getExtensionHistory'])
            ->middleware($perm(...FranchisePermissions::for('Companies', 'show')))
            ->name('franchise.contracts.extend-history');

        Route::get('products/pending/{id}', [FranchiseProductsController::class, 'getPendingProducts'])
            ->middleware($perm(...FranchisePermissions::for('Products', 'show')));

        Route::post('products/approve-action', [FranchiseProductsController::class, 'handleApprovalAction'])
            ->middleware($perm(...FranchisePermissions::for('Products', 'update')))
            ->name('approve-action');
        Route::get('branches', [FranchiseBranchController::class, 'index'])
            ->middleware($perm(...FranchisePermissions::for('Branches', 'show')))
            ->name('branches.index');
        Route::post('branches', [FranchiseBranchController::class, 'store'])
            ->middleware($perm(...FranchisePermissions::for('Branches', 'create')))
            ->name('branches.store');
        Route::get('branches/{id}/edit', [FranchiseBranchController::class, 'edit'])
            ->middleware($perm(...FranchisePermissions::for('Branches', 'update')))
            ->name('branches.edit');
        Route::put('branches/{id}', [FranchiseBranchController::class, 'update'])
            ->middleware($perm(...FranchisePermissions::for('Branches', 'update')))
            ->name('branches.update');
        Route::delete('branches/{id}', [FranchiseBranchController::class, 'destroy'])
            ->middleware($perm(...FranchisePermissions::for('Branches', 'delete')))
            ->name('branches.destroy');

        Route::get('products-management', [FranchiseProductsController::class, 'index'])
            ->middleware($perm(...FranchisePermissions::for('Products', 'show')))
            ->name('products.index');
        Route::get('products/permissions/{id}', [FranchiseProductsController::class, 'getFranchiseProducts'])
            ->middleware($perm(...FranchisePermissions::for('Products', 'show')))
            ->name('products.permissions');
        Route::post('products/update', [FranchiseProductsController::class, 'update'])
            ->middleware($perm(...FranchisePermissions::for('Products', 'update')))
            ->name('products.update');

        Route::prefix('/custom-menus')->group(function () use ($perm) {
            Route::get('/', [FranchiseCustomMenuController::class, 'index'])
                ->middleware($perm(...FranchisePermissions::for('Menus', 'show')))
                ->name('franchise.custom_menus.index');
            Route::get('/get-data/{id}', [FranchiseCustomMenuController::class, 'getFranchiseData'])
                ->middleware($perm(...FranchisePermissions::for('Menus', 'show')));
            Route::post('/update', [FranchiseCustomMenuController::class, 'update'])
                ->middleware($perm(...FranchisePermissions::for('Menus', 'update')))
                ->name('custom_menus.update');
        });

        Route::get('companies', [FranchiseCompanyController::class, 'index'])
            ->middleware($perm(...FranchisePermissions::menuShowAny()))
            ->name('companies.index');
        Route::get('companies/{company}', [FranchiseCompanyController::class, 'show'])
            ->middleware($perm(...FranchisePermissions::for('Companies', 'show')))
            ->name('companies.show');
        Route::post('companies', [FranchiseCompanyController::class, 'store'])
            ->middleware($perm(...FranchisePermissions::for('Companies', 'create')))
            ->name('companies.store');
        Route::put('companies/{company}', [FranchiseCompanyController::class, 'update'])
            ->middleware($perm(...FranchisePermissions::for('Companies', 'update')))
            ->name('companies.update');
        Route::patch('companies/{company}', [FranchiseCompanyController::class, 'update'])
            ->middleware($perm(...FranchisePermissions::for('Companies', 'update')));
        Route::delete('companies/{company}', [FranchiseCompanyController::class, 'destroy'])
            ->middleware($perm(...FranchisePermissions::for('Companies', 'delete')))
            ->name('companies.destroy');

        Route::prefix('contracts')->name('contracts.')->group(function () use ($perm) {
            Route::post('/store', [FranchiseContractController::class, 'store'])
                ->middleware($perm(...FranchisePermissions::for('Companies', 'create')))
                ->name('store');
            Route::delete('/{id}', [FranchiseContractController::class, 'destroy'])
                ->middleware($perm(...FranchisePermissions::for('Companies', 'delete')))
                ->name('destroy');

            Route::get('/{id}/edit', [FranchiseContractController::class, 'edit'])
                ->middleware($perm(...FranchisePermissions::for('Companies', 'update')))
                ->name('edit');
            Route::put('/{id}', [FranchiseContractController::class, 'update'])
                ->middleware($perm(...FranchisePermissions::for('Companies', 'update')))
                ->name('update');
        });
    });
});
