<?php

use Illuminate\Support\Facades\Route;
use Modules\Expense\Http\Controllers\ExpenseController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    Route::middleware(['auth'])->group(function () {

        Route::prefix('expenses')->name('expenses.')->group(function () {

            Route::get('manage', [ExpenseController::class, 'manage'])->name('manage');
            Route::get('manage/create', [ExpenseController::class, 'create'])->name('manage.create');
            Route::post('manage', [ExpenseController::class, 'store'])->name('manage.store');
            Route::get('manage/{id}', [ExpenseController::class, 'show'])->whereNumber('id')->name('manage.show');
            Route::get('manage/{id}/edit', [ExpenseController::class, 'edit'])->name('manage.edit');
            Route::put('manage/{id}', [ExpenseController::class, 'update'])->name('manage.update');
            Route::delete('manage/{id}', [ExpenseController::class, 'destroy'])->whereNumber('id')->name('manage.destroy');

            Route::delete('manage/{expense}/attachments/{attachment}', [ExpenseController::class, 'attachmentDestroy'])
                ->name('manage.attachments.destroy');
        });
    });
});
