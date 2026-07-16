<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            // \App\Http\Middleware\Authenticate::class,
        ]);
        
        $middleware->alias([
            'merchant.password.change' => \App\Http\Middleware\RequirePasswordChange::class,
            'merchant.api.auth'        => \App\Http\Middleware\MerchantApiAuthenticate::class,
        ]);
        
        // Configure API authentication to return JSON instead of redirecting
        $middleware->statefulApi();
        
        // Configure Authenticate middleware to not redirect for API routes
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('api/*') || $request->is('v1/*') || $request->expectsJson() || $request->wantsJson()) {
                return null; // Don't redirect, let exception handler catch it
            }
            if ($request->is('admin*')) {
                return route('admin.login');
            }
            return route('merchant.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle unauthenticated API requests - return JSON instead of redirecting
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->is('v1/*') || $request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        });
        
        // Also handle route not found for API
        $exceptions->render(function (\Symfony\Component\Routing\Exception\RouteNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->is('v1/*') || $request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Route not found.',
                ], 404);
            }
        });
    })->create();
