<?php

use Modules\Zatca\Http\Controllers\ZatcaDocumentController;
use Modules\Zatca\Http\Controllers\ZatcaEinvoicingController;
use Modules\Zatca\Http\Controllers\ZatcaSettingController;
use Modules\Zatca\Support\ZatcaPermissions;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::middleware(['auth'])->group(function () {
        Route::get('zatca-einvoicing', [ZatcaEinvoicingController::class, 'index'])
            ->middleware('zatca.perm:'.ZatcaPermissions::EINVOICING_SHOW)
            ->name('zatca.einvoicing.index');

        Route::get('zatca-settings', [ZatcaSettingController::class, 'edit'])
            ->middleware('zatca.perm:'.ZatcaPermissions::SETTINGS_SHOW.','.ZatcaPermissions::OPERATIONS_SHOW)
            ->name('zatca.settings.edit');

        Route::put('zatca-settings', [ZatcaSettingController::class, 'update'])
            ->middleware('zatca.perm:'.ZatcaPermissions::SETTINGS_UPDATE)
            ->name('zatca.settings.update');

        Route::post('zatca-settings/regenerate', [ZatcaSettingController::class, 'regenerate'])
            ->middleware('zatca.perm:'.ZatcaPermissions::REGENERATE_CREATE)
            ->name('zatca.settings.regenerate');

        Route::post('zatca-settings/sync-sell', [ZatcaSettingController::class, 'syncSell'])
            ->middleware('zatca.perm:'.ZatcaPermissions::SYNC_CREATE)
            ->name('zatca.settings.sync-sell');

        Route::put('zatca-settings/operations', [ZatcaSettingController::class, 'updateOperations'])
            ->middleware('zatca.perm:'.ZatcaPermissions::OPERATIONS_UPDATE)
            ->name('zatca.settings.operations');

        Route::post('zatca-settings/purge-sandbox', [ZatcaSettingController::class, 'purgeSandbox'])
            ->middleware('zatca.perm:'.ZatcaPermissions::PURGE_SANDBOX_CREATE)
            ->name('zatca.settings.purge-sandbox');

        Route::get('zatca-documents/{transactionId}/pdf', [ZatcaDocumentController::class, 'pdf'])
            ->middleware('zatca.perm:'.ZatcaPermissions::DOCUMENTS_PRINT)
            ->name('zatca.documents.pdf');

        Route::get('zatca-documents/{transactionId}/preview', [ZatcaDocumentController::class, 'preview'])
            ->middleware('zatca.perm:'.ZatcaPermissions::DOCUMENTS_SHOW)
            ->name('zatca.documents.preview');

        Route::get('zatca-documents/{transactionId}/xml', [ZatcaDocumentController::class, 'xml'])
            ->middleware('zatca.perm:'.ZatcaPermissions::DOCUMENTS_SHOW)
            ->name('zatca.documents.xml');

        Route::get('zatca-documents/{transactionId}/qr', [ZatcaDocumentController::class, 'qr'])
            ->middleware('zatca.perm:'.ZatcaPermissions::DOCUMENTS_SHOW)
            ->name('zatca.documents.qr');
    });
});
