<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Exceptions\Handler as ExceptionHandler;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API middleware - CORS must be before response
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ], append: [
            \App\Http\Middleware\ApiResponseMiddleware::class,
            \App\Http\Middleware\LogApiRequests::class,
            \App\Http\Middleware\RateLimitMiddleware::class,
        ]);

        // Throttle API requests
        $middleware->throttleApi('300,1');

        // Trust proxies
        $middleware->trustProxies(at: ['*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Use custom exception handler
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->expectsJson();
        });
    })->create();
