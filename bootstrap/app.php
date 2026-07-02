<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Phase 1 (Routing Migration): load the new REST surface inside the
        // "web" middleware group so it shares the app's session auth + CSRF
        // protection, under the "/api" prefix and "api." route-name prefix.
        then: function () {
            Route::middleware('web')
                ->prefix('api')
                ->name('api.')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // Keep every request on one local host (localhost vs 127.0.0.1) so the
        // session cookie isn't split across hosts — fixes "logout on refresh"
        // and the chat showing no answer (its /api/* calls were bouncing to login).
        $middleware->prependToGroup('web', \App\Http\Middleware\EnforceCanonicalHost::class);

        // Exclude provider-token routes from CSRF so the browser extension
        // (which can't read the CSRF token from its service worker) can POST
        // session tokens back to the app. The routes are still protected by
        // the web middleware's session auth.
        $middleware->validateCsrfTokens(except: [
            'api/provider-tokens',
            'api/provider-tokens/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
