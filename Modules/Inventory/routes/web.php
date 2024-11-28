<?php

use App\Http\Middleware\AuthenticateJWT;
use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\InventoryOperationController;
use Modules\Inventory\Http\Controllers\PrepController;
use Modules\Inventory\Http\Controllers\ProductInventoryController;
use Modules\Inventory\Http\Controllers\PurchaseOrderController;
use Modules\Inventory\Http\Controllers\RMAController;
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
    AuthenticateJWT::class
])->group( function () {
    Route::resource('productInventory', ProductInventoryController::class)->names('productInventory');
    Route::get('productInventoryList', [ProductInventoryController::class, 'getProductInventories'])->name('productInventoryList');
    Route::get('getProductInventory/{id}', [ProductInventoryController::class, 'getProductInventory']);
    Route::resource('purchaseOrder', PurchaseOrderController::class)->names('purchaseOrder');
    Route::resource('prep', PrepController::class)->names('prep');
    Route::resource('rma', RMAController::class)->names('rma');
    Route::resource('inventoryOperation', InventoryOperationController::class)->names('inventoryOperation');
    Route::get('/inventoryOperationList/{type?}', [InventoryOperationController::class, 'getinventoryOperations'])->name('inventoryOperationList');;
    Route::post('/statusUpdate', [InventoryOperationController::class, 'statusUpdate'])->name('statusUpdate');
    Route::post('/updateRecive', [PurchaseOrderController::class, 'updateRecive'])->name('updateRecive');
    Route::get('/purchaseOrder/{id}/recieve', [PurchaseOrderController::class, 'recieve'])->name('purchaseOrder.recieve');
    Route::post('/inventoryOperation/store/{type}', [InventoryOperationController::class, 'store'])->name('inventoryOperationStore');
});
