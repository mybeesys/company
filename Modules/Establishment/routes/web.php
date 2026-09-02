<?php

use Illuminate\Support\Facades\Route;
use Modules\Establishment\Http\Controllers\CashierCatalogSettingsController;
use Modules\Establishment\Http\Controllers\CompanyController;
use Modules\Establishment\Http\Controllers\DeviceController;
use Modules\Establishment\Http\Controllers\EstablishmentController;
use Modules\Establishment\Support\EstablishmentPermissions;
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

    Route::middleware('auth')->group(function () {
        $perm = fn (string ...$names) => 'dashboard.perm:'.implode(',', $names);

        Route::controller(CashierCatalogSettingsController::class)->prefix('settings')->name('cashier-settings.')->group(function () use ($perm) {
            Route::get('/cashier-payment-methods', 'paymentMethods')
                ->middleware($perm(EstablishmentPermissions::ESTABLISHMENT_UPDATE))
                ->name('payment-methods');
            Route::patch('/cashier-payment-methods', 'updatePaymentMethods')
                ->middleware($perm(EstablishmentPermissions::ESTABLISHMENT_UPDATE))
                ->name('payment-methods.update');
            Route::get('/internal-consumption-types', 'internalConsumption')
                ->middleware($perm(EstablishmentPermissions::ESTABLISHMENT_UPDATE))
                ->name('internal-consumption');
            Route::patch('/internal-consumption-types', 'updateInternalConsumption')
                ->middleware($perm(EstablishmentPermissions::ESTABLISHMENT_UPDATE))
                ->name('internal-consumption.update');
            Route::get('/service-fees', 'serviceFees')
                ->middleware($perm(EstablishmentPermissions::ESTABLISHMENT_UPDATE))
                ->name('service-fees');
            Route::patch('/service-fees', 'updateServiceFees')
                ->middleware($perm(EstablishmentPermissions::ESTABLISHMENT_UPDATE))
                ->name('service-fees.update');
        });

        Route::controller(EstablishmentController::class)->prefix('establishment')->name('establishments.')->group(function () use ($perm) {
            Route::get('', 'index')
                ->middleware($perm(EstablishmentPermissions::ESTABLISHMENTS_SHOW, EstablishmentPermissions::ESTABLISHMENT_SHOW))
                ->name('index');
            Route::get('/{id}/edit', 'edit')
                ->middleware($perm(EstablishmentPermissions::ESTABLISHMENT_UPDATE))
                ->name('edit');
            Route::get('/create', 'create')
                ->middleware($perm(EstablishmentPermissions::ESTABLISHMENT_CREATE))
                ->name('create');
            Route::post('/store', 'store')
                ->middleware($perm(EstablishmentPermissions::ESTABLISHMENT_CREATE))
                ->name('store');
            Route::patch('/{establishment}', 'update')
                ->middleware($perm(EstablishmentPermissions::ESTABLISHMENT_UPDATE))
                ->name('update');
            Route::post('/create/validate', 'createLiveValidation')
                ->middleware($perm(EstablishmentPermissions::ESTABLISHMENT_CREATE, EstablishmentPermissions::ESTABLISHMENT_UPDATE))
                ->name('create.validation');

            Route::post('/restore/{establishment}', 'restore')
                ->middleware($perm(EstablishmentPermissions::ESTABLISHMENT_UPDATE))
                ->name('restore');
            Route::delete('/{establishment}', 'softDelete')
                ->middleware($perm(EstablishmentPermissions::ESTABLISHMENT_DELETE))
                ->name('delete');
            Route::delete('/force-delete/{establishment}', 'forceDelete')
                ->middleware($perm(EstablishmentPermissions::ESTABLISHMENT_DELETE))
                ->name('forceDelete');
        });

        Route::controller(CompanyController::class)->prefix('company')->name('companies.')->group(function () use ($perm) {
            Route::get('/setting', 'index')
                ->middleware($perm(EstablishmentPermissions::COMPANY_SHOW, \Modules\General\Support\SettingPermissions::GENERAL_SHOW))
                ->name('settings');
            Route::patch('/{id}', 'update')
                ->middleware($perm(EstablishmentPermissions::COMPANY_UPDATE, \Modules\General\Support\SettingPermissions::GENERAL_UPDATE))
                ->name('update');
            Route::post('/update/validate', 'updateLiveValidation')
                ->middleware($perm(EstablishmentPermissions::COMPANY_UPDATE, \Modules\General\Support\SettingPermissions::GENERAL_UPDATE))
                ->name('update.validation');
        });

        Route::get('/devices', [DeviceController::class, 'index'])
            ->middleware($perm(...EstablishmentPermissions::deviceShowAny()))
            ->name('device.index');
        Route::post('/devices/store', [DeviceController::class, 'store'])
            ->middleware($perm(EstablishmentPermissions::ESTABLISHMENT_UPDATE))
            ->name('device.store');
        Route::get('/devices/establishment', [DeviceController::class, 'getEstablishment'])
            ->middleware($perm(EstablishmentPermissions::ESTABLISHMENT_UPDATE))
            ->name('device.establishment');
        Route::delete('/devices/{id}', [DeviceController::class, 'destroy'])
            ->middleware($perm(EstablishmentPermissions::ESTABLISHMENT_UPDATE, EstablishmentPermissions::ESTABLISHMENT_DELETE));
    });
});
