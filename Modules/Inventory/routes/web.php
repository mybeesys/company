<?php

use App\Http\Middleware\AuthenticateJWT;
use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\IngredientInventoryController;
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
use Modules\Inventory\Http\Controllers\Import\OpenInventoryImportController;
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
    PreventAccessFromCentralDomains::class
])->group( function () {
    Route::resource('productInventory', ProductInventoryController::class)->names('productInventory');
    Route::resource('purchaseOrder', PurchaseOrderController::class)->names('purchaseOrder');
    Route::get('/purchaseOrderReport/{id}/generatePDF', [PurchaseOrderReportController::class, 'generatePDF'])->name('generatePDF');
    Route::get('/purchaseOrderReport/{id}/purchase_order_pdf', [PurchaseOrderReportController::class, 'purchase_order_pdf'])->name('purchaseOrder.purchase_order_pdf');
    Route::resource('purchaseOrderReport', PurchaseOrderReportController::class)->names('purchaseOrderReport');
    Route::get('/productInventoryReport/{id}/productInventory_pdf', [ProductInventoryReportController::class, 'productInventory_pdf'])->name('productInventory.productInventory_pdf');
    Route::resource('productInventoryReport', ProductInventoryReportController::class)->names('productInventoryReport');
    
    Route::get('productInventoryList', [ProductInventoryController::class, 'getProductInventories'])->name('productInventoryList');
    Route::get('getProductInventory/{id}', [ProductInventoryController::class, 'getProductInventory']);
    Route::resource('ingredientInventory', IngredientInventoryController::class)->names('ingredientInventory');
    Route::get('ingredientInventoryList', [IngredientInventoryController::class, 'getIngredientInventories'])->name('ingredientInventoryList');
    Route::get('getIngredientInventory/{id}', [IngredientInventoryController::class, 'getIngredientInventory']);
    Route::resource('prep', PrepController::class)->names('prep');
    Route::get('prepList', [PrepController::class, 'getPreps'])->name('prepList');
    Route::resource('rma', RMAController::class)->names('rma');
    Route::resource('waste', WasteController::class)->names('waste');
    Route::get('wasteList', [WasteController::class, 'getWastes'])->name('wasteList');
    Route::resource('transfer', TransferController::class)->names('transfer');
    Route::resource('inventoryOperation', InventoryOperationController::class)->names('inventoryOperation');
    Route::get('/inventoryOperationList/{type?}', [InventoryOperationController::class, 'getinventoryOperations'])->name('inventoryOperationList');;
    Route::post('/statusUpdate', [InventoryOperationController::class, 'statusUpdate'])->name('statusUpdate');
    Route::post('/updateRecive', [PurchaseOrderController::class, 'updateRecive'])->name('updateRecive');
    Route::get('/purchaseOrder/{id}/recieve', [PurchaseOrderController::class, 'recieve'])->name('purchaseOrder.recieve');
    Route::post('/inventoryOperation/store/{type}', [InventoryOperationController::class, 'store'])->name('inventoryOperationStore');
    Route::get('warehouselist', [WarehouseController::class, 'getWarehouselist'])->name('warehouselist');
    Route::resource('warehouse', WarehouseController::class)->names('warehouse');
    Route::post('/openInventoryImport/upload', [OpenInventoryImportController::class, 'upload']);
    Route::post('/openInventoryImport/readData', [OpenInventoryImportController::class, 'readData']);
    Route::get('/openInventoryImport/import', [OpenInventoryImportController::class, 'import'])->name('openInventoryImport.import');

});
