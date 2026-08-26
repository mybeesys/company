<?php

use Modules\Zatca\Http\Controllers\ZatcaDocumentController;
use Modules\Zatca\Http\Controllers\ZatcaEinvoicingController;
use Modules\Zatca\Http\Controllers\ZatcaSettingController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::middleware(['auth'])->group(function () {
        Route::get('zatca-einvoicing', [ZatcaEinvoicingController::class, 'index'])->name('zatca.einvoicing.index');

        Route::get('zatca-settings', [ZatcaSettingController::class, 'edit'])->name('zatca.settings.edit');
        Route::put('zatca-settings', [ZatcaSettingController::class, 'update'])->name('zatca.settings.update');
        Route::post('zatca-settings/regenerate', [ZatcaSettingController::class, 'regenerate'])->name('zatca.settings.regenerate');
        Route::post('zatca-settings/sync-sell', [ZatcaSettingController::class, 'syncSell'])->name('zatca.settings.sync-sell');
        Route::put('zatca-settings/operations', [ZatcaSettingController::class, 'updateOperations'])->name('zatca.settings.operations');
        Route::post('zatca-settings/purge-sandbox', [ZatcaSettingController::class, 'purgeSandbox'])->name('zatca.settings.purge-sandbox');

        Route::get('zatca-documents/{transactionId}/pdf', [ZatcaDocumentController::class, 'pdf'])->name('zatca.documents.pdf');
        Route::get('zatca-documents/{transactionId}/preview', [ZatcaDocumentController::class, 'preview'])->name('zatca.documents.preview');
        Route::get('zatca-documents/{transactionId}/xml', [ZatcaDocumentController::class, 'xml'])->name('zatca.documents.xml');
        Route::get('zatca-documents/{transactionId}/qr', [ZatcaDocumentController::class, 'qr'])->name('zatca.documents.qr');
    });
});
