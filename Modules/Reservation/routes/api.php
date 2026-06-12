<?php

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    require __DIR__.'/Api/order.php';

    // تحقق توكن Socket.IO من Node (tenant domain)
    Route::get('/verify-socket-token', function (\Illuminate\Http\Request $request) {
        if (! \App\Support\SanctumBearerValidator::isValid($request->bearerToken())) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json(['ok' => true]);
    });
});
