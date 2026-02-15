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
            $origin = $request->header('Origin') ?? $request->header('Referer');

            // Si tiene Authorization header, verificar que el usuario esté autenticado
            // Esto evita que tokens robados sean usados desde orígenes no permitidos
            if ($request->header('Authorization')) {
                // Solo permitir si hay un usuario autenticado Y el origen es válido
                // O si es una petición de herramientas de desarrollo (debug)
                if (auth('sanctum')->check()) {
                    // Usuario autenticado: verificar origen
                    if ($origin && ! in_array($this->parseOrigin($origin), $this->allowedOrigins)) {
                        // En producción, denegar; en desarrollo, permitir con warning
                        if (app()->environment(['production'])) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Origen no permitido para usuario autenticado.',
                            ], 403);
                        }
                    }
                } elseif ($origin) {
                    // Sin autenticación: verificar origen estrictamente
                    $parsedOrigin = $this->parseOrigin($origin);
                    if (! in_array($parsedOrigin, $this->allowedOrigins)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Origen no permitido. Posible ataque CSRF.',
                        ], 403);
                    }
                }
            } elseif ($origin) {
                // Sin Authorization header: verificar origen
                $parsedOrigin = $this->parseOrigin($origin);
                if (! in_array($parsedOrigin, $this->allowedOrigins)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Origen no permitido. Posible ataque CSRF.',
                    ], 403);
                }
            }
        }

        return $next($request);
    }

    /**
     * Parse and clean origin URL
     */
    private function parseOrigin(string $origin): string
    {
        $parsedOrigin = parse_url($origin);

        return ($parsedOrigin['scheme'] ?? 'http').'://'.
               ($parsedOrigin['host'] ?? '').
               (isset($parsedOrigin['port']) ? ':'.$parsedOrigin['port'] : '');
    }
}
