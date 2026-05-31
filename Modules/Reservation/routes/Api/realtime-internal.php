<?php

use Illuminate\Support\Facades\Route;
use Modules\Reservation\Http\Controllers\Api\RealtimeInternalController;

Route::middleware(['socket.internal'])->prefix('internal/realtime')->group(function () {
    Route::get('tables-snapshot', [RealtimeInternalController::class, 'tablesSnapshot']);
    Route::get('tables/{tableId}/order', [RealtimeInternalController::class, 'tableOrder']);
    Route::get('kitchen-orders', [RealtimeInternalController::class, 'kitchenOrders']);
});
