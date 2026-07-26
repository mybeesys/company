<?php

use App\Http\Middleware\CentralAppAuthenticate;
use App\Http\Middleware\DetectEmbedRequest;
use App\Http\Middleware\VerifySocketInternalSecret;
use App\Http\Middleware\CleanJsonNoiseMiddleware;
use App\Http\Middleware\EnsureHasSubscription;
use App\Http\Middleware\EnsureModuleEntitlement;
use App\Http\Middleware\LocalizationMiddleware;
use App\Http\Middleware\SetApiLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Exceptions\FiscalPeriodException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth-central' => CentralAppAuthenticate::class,
            'socket.internal' => VerifySocketInternalSecret::class,
            'entitled' => EnsureModuleEntitlement::class,
        ]);
        $middleware->web(append: [
            DetectEmbedRequest::class,
            LocalizationMiddleware::class,
            EnsureHasSubscription::class,
            EnsureModuleEntitlement::class,
            CleanJsonNoiseMiddleware::class,
        ]);
        $middleware->api(append: [
            SetApiLocale::class,
            EnsureHasSubscription::class,
            EnsureModuleEntitlement::class,
            CleanJsonNoiseMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (FiscalPeriodException $e, $request) {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        });
    })->create();
