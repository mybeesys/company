<?php

use Illuminate\Support\Facades\Route;
use Modules\General\Http\Controllers\FavoriteController;
use Modules\General\Http\Controllers\GeneralController;
use Modules\General\Http\Controllers\NotificationController;
use Modules\General\Http\Controllers\NotificationSettingController;
use Modules\General\Http\Controllers\PaymentMethodsController;
use Modules\General\Http\Controllers\TaxController;
use Modules\General\Http\Controllers\TransactionController;
use Modules\General\Support\SettingPermissions;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for the application. These
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
        $perm = fn (string ...$names) => 'dashboard.perm:'.implode(',', $names);

        Route::get('/subscription', [GeneralController::class, 'subscription'])->name('subscription');

        Route::post('store-sidebar-status', [GeneralController::class, 'storeSidebarState'])->name('store-sidebar-status');

        Route::post('store-notifications-settings/{notificationType}', [NotificationSettingController::class, 'storeNotificationsSettings'])
            ->middleware($perm(...SettingPermissions::for('notifications', 'update')))
            ->name('store-notifications-settings');

        Route::post('store-notification-settings-parameters', [NotificationSettingController::class, 'storeNotificationSettingsParameters'])
            ->middleware($perm(...SettingPermissions::for('mail', 'update'), ...SettingPermissions::for('sms', 'update')))
            ->name('store-notification-settings-parameters');

        Route::get('taxes', [TaxController::class, 'index'])
            ->middleware($perm(...SettingPermissions::for('taxes', 'show')))
            ->name('taxes');
        Route::post('store-tax', [TaxController::class, 'store'])
            ->middleware($perm(...SettingPermissions::for('taxes', 'create')))
            ->name('store-tax');
        Route::post('update-tax', [TaxController::class, 'update'])
            ->middleware($perm(...SettingPermissions::for('taxes', 'update')))
            ->name('update-tax');
        Route::get('delete-tax/{id}', [TaxController::class, 'destroy'])
            ->middleware($perm(...SettingPermissions::for('taxes', 'delete')))
            ->name('delete-tax');

        Route::get('payment-methods', [PaymentMethodsController::class, 'index'])
            ->middleware($perm(...SettingPermissions::for('general', 'show')))
            ->name('payment-methods');
        Route::post('update-payment-methods/{id}', [PaymentMethodsController::class, 'update'])
            ->middleware($perm(...SettingPermissions::for('general', 'update')))
            ->name('update-payment-methods');

        Route::get('transaction-show/{id}', [TransactionController::class, 'show'])->name('transaction-show');
        Route::get('show-receipts-payments/{id}', [TransactionController::class, 'showReceiptsPayments'])->name('show-receipts-payments');
        Route::get('show-receipts-payments-export-pdf/{id}', [TransactionController::class, 'exportReceiptsPaymentsPDF'])->name('show-receipts-payments-export-pdf');

        Route::get('transaction-print/{id}', [TransactionController::class, 'print'])->name('transaction-print');
        Route::get('transaction-payment-print/{id}', [TransactionController::class, 'paymentPrint'])->name('transaction-payment-print');
        Route::get('transaction-export-pdf/{id}', [TransactionController::class, 'exportPDF'])->name('transaction-export-pdf');

        Route::get('transaction-show-payments/{id}', [TransactionController::class, 'showPayments'])->name('transaction-show-payments');
        Route::post('add-payment', [TransactionController::class, 'addPayment'])->name('add-payment');

        Route::get('general-setting', [GeneralController::class, 'setting'])
            ->middleware($perm(...SettingPermissions::pageShowAny()))
            ->name('general-setting');
        Route::post('update-prefix', [GeneralController::class, 'updatePrefix'])
            ->middleware($perm(...SettingPermissions::for('prefix', 'update')))
            ->name('update-prefix');
        Route::post('update-currency', [GeneralController::class, 'updateCurrency'])
            ->middleware($perm(...SettingPermissions::for('general', 'update')))
            ->name('update-currency');
        Route::post('update-modules', [GeneralController::class, 'updateModules'])
            ->middleware($perm(...SettingPermissions::for('modules', 'update')))
            ->name('update-modules');
        Route::post('/update-inventory-policy', [GeneralController::class, 'updateInventorySettings'])
            ->middleware($perm(...SettingPermissions::for('inventory policy', 'update')))
            ->name('update-inventory-policy');

        Route::post('/update-unit', [GeneralController::class, 'updateUnit'])
            ->middleware($perm(...SettingPermissions::for('default unit', 'update')))
            ->name('update-unit');
        Route::post('update-inventory-costing-method', [GeneralController::class, 'updateInventoryCostingMethod'])
            ->middleware($perm(...SettingPermissions::for('inventory costing', 'update')))
            ->name('update-inventory-costing-method');
        Route::get('preview-inventory-costing-rebuild', [GeneralController::class, 'previewInventoryCostingRebuild'])
            ->middleware($perm(...SettingPermissions::for('inventory costing', 'show'), ...SettingPermissions::for('inventory costing', 'update')))
            ->name('preview-inventory-costing-rebuild');
        Route::post('rebuild-inventory-costing', [GeneralController::class, 'rebuildInventoryCosting'])
            ->middleware($perm(...SettingPermissions::for('inventory costing', 'update')))
            ->name('rebuild-inventory-costing');

        Route::post('notification-mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notification-mark-all-as-read');
        Route::post('notification-delete', [NotificationController::class, 'destroy'])->name('notification-delete');
        Route::get('fetch-notification', [NotificationController::class, 'fetchNotification'])->name('fetch-notification');

        Route::post('save-nots-terms', [GeneralController::class, 'saveNotsTerms'])
            ->middleware($perm(...SettingPermissions::for('invoice', 'update')))
            ->name('save-nots-terms');

        Route::post('/favorites/toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
        Route::get('/invoice-settings', [GeneralController::class, 'getInvoiceSettings'])
            ->middleware($perm(...SettingPermissions::for('invoice', 'show')))
            ->name('invoice-settings-get');
        Route::post('/update-reward-points', [GeneralController::class, 'updateRewardPoints'])
            ->middleware($perm(...SettingPermissions::for('reward points', 'update')))
            ->name('update-reward-points');
        Route::post('/invoice-settings-update', [GeneralController::class, 'updateInvoiceSetting'])
            ->middleware($perm(...SettingPermissions::for('invoice', 'update')))
            ->name('invoice-settings-update');

    });
});
