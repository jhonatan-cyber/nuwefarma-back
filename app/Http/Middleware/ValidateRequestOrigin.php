<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware adicional para validar el origen de las peticiones
 * Protección extra contra CSRF
 */
class ValidateRequestOrigin
{
    private array $allowedOrigins;

    public function __construct()
    {
        $origins = env('CORS_ORIGINS', 'http://localhost:5173,http://127.0.0.1:5173,http://localhost:3000,http://127.0.0.1:3000');
        $this->allowedOrigins = array_map('trim', explode(',', $origins));
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Validar solo en métodos que modifican datos (protección CSRF)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            // Si el request tiene Authorization header (bearer token), permitir
            // Esto permite que Swagger, Postman, y apps móviles funcionen
            if ($request->header('Authorization')) {
                return $next($request);
            }
            
            $origin = $request->header('Origin') ?? $request->header('Referer');
            
            if ($origin) {
                // Limpiar el origen (quitar path si viene del Referer)
                $parsedOrigin = parse_url($origin);
                $cleanOrigin = ($parsedOrigin['scheme'] ?? 'http') . '://' . 
                              ($parsedOrigin['host'] ?? '') . 
                              (isset($parsedOrigin['port']) ? ':' . $parsedOrigin['port'] : '');
                
                // Verificar si el origen está en la lista permitida
                if (!in_array($cleanOrigin, $this->allowedOrigins)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Origen no permitido. Posible ataque CSRF.',
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}
