<?php

use App\Http\Middleware\EnsureAuthenticated;
use App\Http\Middleware\SetLocale;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Set the request language (EN/ID) on every web request.
        $middleware->web(append: [
            SetLocale::class,
        ]);

        // Protect a route group with ->middleware('auth.api').
        $middleware->alias([
            'auth.api' => EnsureAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Never show a stack trace when the Go API is down — degrade gracefully.
        $exceptions->render(function (ApiUnavailableException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 503);
            }

            return response()->view('errors.api-unavailable', ['message' => $e->getMessage()], 503);
        });
    })->create();
