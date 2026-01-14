<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar que el User-Agent no cambie durante la sesión
 * Previene session hijacking y algunos ataques CSRF
 */
class VerifyUserAgent
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            // Si usa bearer token (Swagger, Postman, apps móviles), no verificar User-Agent
            // Esta protección solo aplica a autenticación con cookies/sesiones
            if ($request->header('Authorization')) {
                return $next($request);
            }
            
            $currentUserAgent = $request->header('User-Agent');
            $sessionUserAgent = $request->session()->get('user_agent');

            if (!$sessionUserAgent) {
                // Primera vez: guardar el User-Agent
                $request->session()->put('user_agent', $currentUserAgent);
            } elseif ($currentUserAgent !== $sessionUserAgent) {
                // User-Agent cambió: posible ataque
                logger()->warning('User-Agent cambió durante sesión activa', [
                    'user_id' => $request->user()->id,
                    'session_ua' => $sessionUserAgent,
                    'current_ua' => $currentUserAgent,
                    'ip' => $request->ip(),
                ]);

                // Invalidar sesión por seguridad
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'success' => false,
                    'message' => 'Sesión inválida. Por favor, inicia sesión nuevamente.',
                ], 401);
            }
        }

        return $next($request);
    }
}
