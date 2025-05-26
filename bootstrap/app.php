<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api([
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,

        ]);
        
        // Define the Accept: application/json header for API routes
        $middleware->group('api', [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);
        
        // Register alias for the auth:sanctum middleware
        $middleware->alias([
            'auth.sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Configure any exception handling for routes like Paystack webhooks here
        // For example, to ignore specific routes in exception reporting:
        // $exceptions->dontReport([
        //     \App\Exceptions\PaystackWebhookException::class,
            // ]);
        
        // Or to customize responses for specific routes:
        $exceptions->renderable(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/v1/webhooks/paystack')) {
                return response()->json(['error' => 'Webhook Error'], 500);
            }
        });
    })->create();
