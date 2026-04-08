<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission'    => \App\Http\Middleware\CheckPermission::class,
            'role'          => \App\Http\Middleware\CheckRole::class,
            'has.role'      => \App\Http\Middleware\CheckUserHasRole::class,
            'check.session' => \App\Http\Middleware\CheckSession::class,
            'role.atpm'     => \App\Http\Middleware\CheckRoleAtpm::class,
            'role.dealer'   => \App\Http\Middleware\CheckRoleDealer::class,
            'check.sync'    => \App\Http\Middleware\CheckSyncRunning::class,
        ]);
        
        // Add security headers to all web requests
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // Configure authentication middleware
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle 401 Unauthorized errors
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'error' => 'You need to authenticate to access this resource.'
                ], 401);
            }

            // For web requests, show custom 401 page
            return response()->view('errors.401', [], 401);
        });
    })->create();
