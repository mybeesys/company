<?php

use Illuminate\Support\Facades\Route;
use Modules\Expense\Http\Controllers\ExpenseController;
use Modules\Accounting\Support\AccountingPermissions;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    Route::middleware(['auth'])->group(function () {

        Route::prefix('expenses')->name('expenses.')->group(function () {
            $perm = fn (string ...$names) => 'dashboard.perm:'.implode(',', $names);

            Route::get('manage', [ExpenseController::class, 'manage'])
                ->middleware($perm(AccountingPermissions::EXPENSES_SHOW))
                ->name('manage');
            Route::get('manage/create', [ExpenseController::class, 'create'])
                ->middleware($perm(AccountingPermissions::EXPENSES_CREATE))
                ->name('manage.create');
            Route::post('manage', [ExpenseController::class, 'store'])
                ->middleware($perm(AccountingPermissions::EXPENSES_CREATE))
                ->name('manage.store');
            Route::get('manage/{id}', [ExpenseController::class, 'show'])
                ->middleware($perm(AccountingPermissions::EXPENSES_SHOW))
                ->whereNumber('id')->name('manage.show');
            Route::get('manage/{id}/edit', [ExpenseController::class, 'edit'])
                ->middleware($perm(AccountingPermissions::EXPENSES_UPDATE))
                ->name('manage.edit');
            Route::put('manage/{id}', [ExpenseController::class, 'update'])
                ->middleware($perm(AccountingPermissions::EXPENSES_UPDATE))
                ->name('manage.update');
            Route::delete('manage/{id}', [ExpenseController::class, 'destroy'])
                ->middleware($perm(AccountingPermissions::EXPENSES_DELETE))
                ->whereNumber('id')->name('manage.destroy');

            Route::delete('manage/{expense}/attachments/{attachment}', [ExpenseController::class, 'attachmentDestroy'])
                ->middleware($perm(AccountingPermissions::EXPENSES_UPDATE))
                ->name('manage.attachments.destroy');
        });
    });
});
