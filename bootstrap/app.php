<?php

use App\Http\Middleware\EnsureApiRole;
use App\Http\Middleware\ForceUpdate;
use App\Http\Middleware\RecordPeakMemory;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->alias([
            'force_update' => ForceUpdate::class,
            'peak_memory' => RecordPeakMemory::class,
            'api_role' => EnsureApiRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);

        // API errors (401, 403, 404, 422) are always JSON, whatever the Accept header
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
