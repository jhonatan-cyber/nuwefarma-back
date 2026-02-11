<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware
        $middleware->use([
            HandleCors::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'verify.origin' => \App\Http\Middleware\ValidateRequestOrigin::class,
            'verify.ua' => \App\Http\Middleware\VerifyUserAgent::class,
            'api.ratelimit' => \App\Http\Middleware\RateLimitApiRequests::class,
            'api.auth' => \App\Http\Middleware\EnsureApiAuthenticated::class,
        ]);

        // Headers de seguridad globales
        $middleware->append(\App\Http\Middleware\SecureHeaders::class);

        // Sanctum SPA: maneja autenticación con bearer tokens automáticamente
        // NO necesitamos validar CSRF para peticiones con bearer tokens
        $middleware->statefulApi();

        // Exentar TODAS las rutas API de validación CSRF
        // Ya que usamos bearer tokens, no necesitamos CSRF
        $middleware->validateCsrfTokens(except: [
            'api/*',  // Todas las rutas API están exentas
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Manejar errores de autenticación no capturados
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }
        });
    })->create();
