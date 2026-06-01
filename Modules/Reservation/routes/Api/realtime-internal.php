<?php

use Illuminate\Support\Facades\Route;
use Modules\Reservation\Http\Controllers\Api\RealtimeInternalController;

Route::middleware(['socket.internal'])->prefix('internal/realtime')->group(function () {
    Route::get('tables-snapshot', [RealtimeInternalController::class, 'tablesSnapshot']);
    Route::get('tables/{tableId}/order', [RealtimeInternalController::class, 'tableOrder']);
    Route::get('kitchen-orders', [RealtimeInternalController::class, 'kitchenOrders']);
    Route::get('establishment-orders/{establishmentId}', [RealtimeInternalController::class, 'establishmentOrders']);

    // تحقق التوكن من Node على نفس السيرفر (127.0.0.1) — يتجنب SSL/timeout
    Route::middleware(['auth-central'])->get('verify-token', function () {
        return response()->json(['ok' => true]);
    });
});
