<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para asegurar que las rutas de API requieran autenticación
 */
class EnsureApiAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log para debugging
        \Log::info('EnsureApiAuthenticated: '.$request->path().' - User: '.($request->user() ? 'authenticated' : 'not authenticated'));

        // Si no hay token de autorización, devolver 401 inmediatamente
        if (! $request->header('Authorization')) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado. Token requerido.',
            ], 401);
        }

        return $next($request);
    }
}
