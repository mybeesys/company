<?php

use App\Http\Controllers\Api\CompanyAuthController;
use App\Support\SanctumBearerValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/company-login', [CompanyAuthController::class, 'login']);
Route::post('/company-logout', [CompanyAuthController::class, 'logout']);

Route::get('/verify-token', function (Request $request) {
    if (! SanctumBearerValidator::isValid($request->bearerToken())) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    return response()->json(['ok' => true]);
});

Route::middleware(['socket.internal'])
    ->prefix('internal/realtime')
    ->group(function () {
        Route::get('verify-token', function (Request $request) {
            if (! SanctumBearerValidator::isValid($request->bearerToken())) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return response()->json(['ok' => true]);
        });
    });

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
