<?php

use Illuminate\Support\Facades\Route;
use Modules\General\Http\Controllers\FavoriteController;
use Modules\General\Http\Controllers\GeneralController;
use Modules\General\Http\Controllers\NotificationController;
use Modules\General\Http\Controllers\NotificationSettingController;
use Modules\General\Http\Controllers\PaymentMethodsController;
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

        Route::get('/subscription', [GeneralController::class, 'subscription'])->name('subscription');

        Route::post('store-sidebar-status', [GeneralController::class, 'storeSidebarState'])->name('store-sidebar-status');

        Route::post('store-notifications-settings/{notificationType}', [NotificationSettingController::class, 'storeNotificationsSettings'])->name('store-notifications-settings');

        Route::post('store-notification-settings-parameters', [NotificationSettingController::class, 'storeNotificationSettingsParameters'])->name('store-notification-settings-parameters');

        Route::get('taxes', [TaxController::class, 'index'])->name('taxes');
        Route::post('store-tax', [TaxController::class, 'store'])->name('store-tax');
        Route::post('update-tax', [TaxController::class, 'update'])->name('update-tax');
        Route::get('delete-tax/{id}', [TaxController::class, 'destroy'])->name('delete-tax');


        Route::get('payment-methods', [PaymentMethodsController::class, 'index'])->name('payment-methods');

        Route::get('transaction-show/{id}', [TransactionController::class, 'show'])->name('transaction-show');
        Route::get('transaction-print/{id}', [TransactionController::class, 'print'])->name('transaction-print');
        Route::get('transaction-payment-print/{id}', [TransactionController::class, 'paymentPrint'])->name('transaction-payment-print');
        Route::get('transaction-export-pdf/{id}', [TransactionController::class, 'exportPDF'])->name('transaction-export-pdf');


        Route::get('transaction-show-payments/{id}', [TransactionController::class, 'showPayments'])->name('transaction-show-payments');
        Route::post('add-payment', [TransactionController::class, 'addPayment'])->name('add-payment');

        Route::get('general-setting', [GeneralController::class, 'setting'])->name('general-setting');
        Route::post('update-prefix', [GeneralController::class, 'updatePrefix'])->name('update-prefix');
        Route::post('update-currency', [GeneralController::class, 'updateCurrency'])->name('update-currency');
        Route::post('update-modules', [GeneralController::class, 'updateModules'])->name('update-modules');


        Route::post('update-inventory-costing-method', [GeneralController::class, 'updateInventoryCostingMethod'])->name('update-inventory-costing-method');



        Route::post('notification-mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notification-mark-all-as-read');
        Route::post('notification-delete', [NotificationController::class, 'destroy'])->name('notification-delete');
        Route::get('fetch-notification', [NotificationController::class, 'fetchNotification'])->name('fetch-notification');

        Route::post('save-nots-terms', [GeneralController::class, 'saveNotsTerms'])->name('save-nots-terms');


        Route::post('/favorites/toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
        Route::get('/invoice-settings', [GeneralController::class, 'getInvoiceSettings'])->name('invoice-settings-get');
        Route::post('/invoice-settings-update', [GeneralController::class, 'updateInvoiceSetting'])->name('invoice-settings-update');

    });
});
