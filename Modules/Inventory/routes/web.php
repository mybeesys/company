<?php

use App\Http\Middleware\AuthenticateJWT;
use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\ProductInventoryController;
use Modules\Inventory\Http\Controllers\PurchaseOrderController;
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
    Route::get('purchaseOrderList', [PurchaseOrderController::class, 'getPurchaseOrders'])->name('purchaseOrderList');;
    Route::post('/poStatusUpdate', [PurchaseOrderController::class, 'poStatusUpdate'])->name('poStatusUpdate');
    Route::post('/updateRecive', [PurchaseOrderController::class, 'updateRecive'])->name('updateRecive');
    Route::get('/purchaseOrder/{id}/recieve', [PurchaseOrderController::class, 'recieve'])->name('purchaseOrder.recieve');
});
