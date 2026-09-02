<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\Import\OpenInventoryImportController;
use Modules\Inventory\Http\Controllers\IngredientInventoryController;
use Modules\Inventory\Http\Controllers\InventoryDashboardController;
use Modules\Inventory\Http\Controllers\InventoryOperationController;
use Modules\Inventory\Http\Controllers\PrepController;
use Modules\Inventory\Http\Controllers\ProductInventoryController;
use Modules\Inventory\Http\Controllers\ProductInventoryReportController;
use Modules\Inventory\Http\Controllers\PurchaseOrderController;
use Modules\Inventory\Http\Controllers\PurchaseOrderReportController;
use Modules\Inventory\Http\Controllers\RMAController;
use Modules\Inventory\Http\Controllers\TransferController;
use Modules\Inventory\Http\Controllers\WarehouseController;
use Modules\Inventory\Http\Controllers\WasteController;
use Modules\Inventory\Support\InventoryPermissions;
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
        $perm = fn (string ...$names) => 'dashboard.perm:'.implode(',', $names);

        Route::resource('productInventory', ProductInventoryController::class)->names('productInventory');
        Route::resource('purchaseOrder', PurchaseOrderController::class)->names('purchaseOrder');
        Route::get('/purchaseOrderReport/{id}/generatePDF', [PurchaseOrderReportController::class, 'generatePDF'])->name('generatePDF');
        Route::get('/purchaseOrderReport/{id}/purchase_order_pdf', [PurchaseOrderReportController::class, 'purchase_order_pdf'])->name('purchaseOrder.purchase_order_pdf');
        Route::resource('purchaseOrderReport', PurchaseOrderReportController::class)->names('purchaseOrderReport');
        Route::get('/productInventoryReport/{id}/productInventory_pdf', [ProductInventoryReportController::class, 'productInventory_pdf'])
            ->middleware($perm(InventoryPermissions::PRODUCT_SHOW))
            ->name('productInventory.productInventory_pdf');
        Route::get('/productInventoryReport/{id}/productInventory_xls', [ProductInventoryReportController::class, 'productInventory_xls'])
            ->middleware($perm(InventoryPermissions::PRODUCT_SHOW))
            ->name('productInventory.productInventory_xls');
        Route::resource('productInventoryReport', ProductInventoryReportController::class)->names('productInventoryReport');
        Route::get('inventory-dashboard', [InventoryDashboardController::class, 'index'])
            ->middleware($perm(InventoryPermissions::DASHBOARD_SHOW))
            ->name('inventory.dashboard');
        Route::get('inventory-dashboard/export/critical-items-csv', [InventoryDashboardController::class, 'exportCriticalItemsCsv'])
            ->middleware($perm(InventoryPermissions::DASHBOARD_SHOW))
            ->name('inventory.dashboard.export.critical-items-csv');
        Route::get('inventory-dashboard/export/movement-csv', [InventoryDashboardController::class, 'exportMovementCsv'])
            ->middleware($perm(InventoryPermissions::DASHBOARD_SHOW))
            ->name('inventory.dashboard.export.movement-csv');
        Route::get('inventory-dashboard/export/movement-pdf', [InventoryDashboardController::class, 'exportMovementPdf'])
            ->middleware($perm(InventoryPermissions::DASHBOARD_SHOW))
            ->name('inventory.dashboard.export.movement-pdf');

        Route::get('productInventoryList', [ProductInventoryController::class, 'getProductInventories'])
            ->middleware($perm(InventoryPermissions::PRODUCT_SHOW))
            ->name('productInventoryList');
        Route::get('productInventorySummary', [ProductInventoryController::class, 'summary'])
            ->middleware($perm(InventoryPermissions::PRODUCT_SHOW))
            ->name('productInventorySummary');
        Route::get('productInventoryCriticalCsv', [ProductInventoryController::class, 'exportCriticalCsv'])
            ->middleware($perm(InventoryPermissions::PRODUCT_SHOW))
            ->name('productInventoryCriticalCsv');
        Route::get('getProductInventory/{id}', [ProductInventoryController::class, 'getProductInventory'])
            ->middleware($perm(InventoryPermissions::PRODUCT_SHOW));
        Route::get('listTransactions', [ProductInventoryController::class, 'listTransactions'])
            ->middleware($perm(InventoryPermissions::PRODUCT_SHOW));
        Route::resource('ingredientInventory', IngredientInventoryController::class)->names('ingredientInventory');
        Route::get('ingredientInventoryList', [IngredientInventoryController::class, 'getIngredientInventories'])->name('ingredientInventoryList');
        Route::get('getIngredientInventory/{id}', [IngredientInventoryController::class, 'getIngredientInventory']);
        Route::resource('prep', PrepController::class)->names('prep');
        Route::get('prepList', [PrepController::class, 'getPreps'])
            ->middleware($perm(InventoryPermissions::PREP_SHOW))
            ->name('prepList');
        Route::get('products/needPreparationList', [PrepController::class, 'needPreparationList'])
            ->middleware($perm(InventoryPermissions::PREP_SHOW))
            ->name('needPreparationList');
        Route::get('products/getIngredientList/{id}', [PrepController::class, 'getIngredientList'])
            ->middleware($perm(InventoryPermissions::PREP_SHOW))
            ->name('getIngredientList');
        Route::get('establishmentList', [PrepController::class, 'establishmentList'])
            ->middleware($perm(InventoryPermissions::PREP_SHOW))
            ->name('establishmentList');
        Route::post('prepareRecipe', [PrepController::class, 'prepareRecipe'])
            ->middleware($perm(InventoryPermissions::PREP_CREATE))
            ->name('prepareRecipe');
        Route::resource('rma', RMAController::class)->names('rma');
        Route::resource('waste', WasteController::class)->names('waste');
        Route::post('storeWaste', [WasteController::class, 'storeWaste'])->name('storeWaste');
        Route::get('wasteList', [WasteController::class, 'getWastes'])
            ->middleware($perm(InventoryPermissions::WASTE_SHOW))
            ->name('wasteList');
        Route::resource('transfer', TransferController::class)->names('transfer');
        Route::get('transferList', [TransferController::class, 'getTransfer'])
            ->middleware($perm(InventoryPermissions::TRANSFER_SHOW))
            ->name('transferList');
        Route::post('transfer/rejected', [TransferController::class, 'rejected'])
            ->middleware($perm(InventoryPermissions::TRANSFER_UPDATE))
            ->name('rejected');
        Route::post('transfer/inTransit', [TransferController::class, 'inTransit'])
            ->middleware($perm(InventoryPermissions::TRANSFER_UPDATE))
            ->name('inTransit');
        Route::post('transfer/full-receiving', [TransferController::class, 'fullReceiving'])
            ->middleware($perm(InventoryPermissions::TRANSFER_UPDATE))
            ->name('fullReceiving');
        Route::get('transfer/{id1}/partial-deliveries/{id2}', [TransferController::class, 'partialDeliveries'])
            ->middleware($perm(InventoryPermissions::TRANSFER_UPDATE))
            ->name('partialDeliveries');
        Route::resource('inventoryOperation', InventoryOperationController::class)->names('inventoryOperation');
        Route::get('/inventoryOperationList/{type?}', [InventoryOperationController::class, 'getinventoryOperations'])->name('inventoryOperationList');
        Route::post('/statusUpdate', [InventoryOperationController::class, 'statusUpdate'])->name('statusUpdate');
        Route::post('/updateRecive', [PurchaseOrderController::class, 'updateRecive'])->name('updateRecive');
        Route::get('/purchaseOrder/{id}/recieve', [PurchaseOrderController::class, 'recieve'])->name('purchaseOrder.recieve');
        Route::post('/inventoryOperation/store/{type}', [InventoryOperationController::class, 'store'])->name('inventoryOperationStore');
        Route::get('warehouselist', [WarehouseController::class, 'getWarehouselist'])->name('warehouselist');
        Route::resource('warehouse', WarehouseController::class)->names('warehouse');
        Route::post('/openInventoryImport/upload', [OpenInventoryImportController::class, 'upload'])
            ->middleware($perm(InventoryPermissions::IMPORT_CREATE));
        Route::post('/openInventoryImport/readData', [OpenInventoryImportController::class, 'readData'])
            ->middleware($perm(InventoryPermissions::IMPORT_CREATE));
        Route::get('/openInventoryImport/import', [OpenInventoryImportController::class, 'import'])
            ->middleware($perm(InventoryPermissions::IMPORT_SHOW))
            ->name('openInventoryImport.import');
    });
});
