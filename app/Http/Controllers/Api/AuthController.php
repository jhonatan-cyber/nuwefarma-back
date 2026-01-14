<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Autenticación', description: 'Operaciones de autenticación y gestión de sesión')]
class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Iniciar sesión',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan.perez@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: '12345678', description: 'El CI del usuario'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login exitoso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Login exitoso'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123def456...'),
                        new OA\Property(
                            property: 'user',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'nombre', type: 'string'),
                                new OA\Property(property: 'apellidos', type: 'string'),
                                new OA\Property(property: 'email', type: 'string'),
                                new OA\Property(property: 'rol', type: 'object'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Credenciales inválidas'),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $usuario = Usuario::where('email', $request->email)->first();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Las credenciales proporcionadas son incorrectas.',
                ], 401);
            }

            // Verificar si el usuario está bloqueado temporalmente
            if ($usuario->bloqueado_hasta && Carbon::now()->lt($usuario->bloqueado_hasta)) {
                $minutosRestantes = Carbon::now()->diffInMinutes($usuario->bloqueado_hasta);
                return response()->json([
                    'success' => false,
                    'message' => "Cuenta bloqueada temporalmente. Intenta de nuevo en {$minutosRestantes} minuto(s).",
                    'bloqueado_hasta' => $usuario->bloqueado_hasta->toIso8601String(),
                ], 429);
            }

            // Verificar credenciales
            if (!Hash::check($request->password, $usuario->password)) {
                // Incrementar intentos fallidos
                $usuario->intentos_fallidos++;
                $usuario->ultimo_intento_fallido = Carbon::now();

                // Bloquear cuenta después de 5 intentos fallidos (15 minutos)
                if ($usuario->intentos_fallidos >= 5) {
                    $usuario->bloqueado_hasta = Carbon::now()->addMinutes(15);
                    $usuario->save();

                    return response()->json([
                        'success' => false,
                        'message' => 'Cuenta bloqueada por múltiples intentos fallidos. Intenta de nuevo en 15 minutos.',
                        'bloqueado_hasta' => $usuario->bloqueado_hasta->toIso8601String(),
                    ], 429);
                }

                $usuario->save();

                return response()->json([
                    'success' => false,
                    'message' => 'Las credenciales proporcionadas son incorrectas.',
                    'intentos_restantes' => 5 - $usuario->intentos_fallidos,
                ], 401);
            }

            // Verificar estado activo
            if ($usuario->estado !== 'activo') {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario está inactivo.',
                ], 401);
            }

            // Login exitoso: resetear intentos fallidos
            $usuario->intentos_fallidos = 0;
            $usuario->bloqueado_hasta = null;
            $usuario->ultimo_intento_fallido = null;
            $usuario->save();

            // DOBLE AUTENTICACIÓN: Sesión + Token
            // 1. Crear sesión para frontend (cookies httpOnly - más seguro contra XSS)
            Auth::login($usuario);

            // 2. Generar token API para Swagger/Postman/Apps móviles
            // Revocar tokens anteriores del usuario
            $usuario->tokens()->delete();
            $token = $usuario->createToken('auth_token')->plainTextToken;

            // Registrar actividad de login exitoso
            ActivityLog::registrar(
                accion: 'login',
                modulo: 'auth',
                descripcion: "Login exitoso de usuario: {$usuario->email}",
                usuarioId: $usuario->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                // Token para Swagger/Postman (bearer token)
                'token' => $token,
                'user' => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'apellidos' => $usuario->apellidos,
                    'ci' => $usuario->ci,
                    'email' => $usuario->email,
                    'telefono' => $usuario->telefono,
                    'foto' => $usuario->foto,
                    'estado' => $usuario->estado,
                    'rol' => $usuario->rol,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Cerrar sesión',
        security: [['bearerAuth' => []]],
        tags: ['Autenticación'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout exitoso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Logout exitoso'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        // Registrar actividad antes de cerrar sesión
        ActivityLog::registrar(
            accion: 'logout',
            modulo: 'auth',
            descripcion: "Logout de usuario: {$request->user()->email}",
            usuarioId: $request->user()->id
        );

        // Limpiar ambos métodos de autenticación
        // 1. Revocar token API (si está usando bearer token)
        if ($request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        // 2. Cerrar sesión (si está usando cookies)
        if ($request->session()->has('_token')) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout exitoso',
        ]);
    }

    #[OA\Get(
        path: '/api/auth/me',
        summary: 'Obtener usuario autenticado',
        security: [['bearerAuth' => []]],
        tags: ['Autenticación'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuario autenticado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'user',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'nombre', type: 'string'),
                                new OA\Property(property: 'apellidos', type: 'string'),
                                new OA\Property(property: 'ci', type: 'string'),
                                new OA\Property(property: 'email', type: 'string'),
                                new OA\Property(property: 'telefono', type: 'string'),
                                new OA\Property(property: 'foto', type: 'string'),
                                new OA\Property(property: 'estado', type: 'string'),
                                new OA\Property(property: 'rol', type: 'object'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        $usuario = $request->user()->load('rol');

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'apellidos' => $usuario->apellidos,
                'ci' => $usuario->ci,
                'email' => $usuario->email,
                'telefono' => $usuario->telefono,
                'direccion' => $usuario->direccion,
                'sueldo' => $usuario->sueldo,
                'foto' => $usuario->foto,
                'estado' => $usuario->estado,
                'rol' => $usuario->rol,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/auth/sessions',
        summary: 'Listar sesiones activas del usuario',
        security: [['bearerAuth' => []]],
        tags: ['Autenticación'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de sesiones activas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'sessions',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'last_used_at', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'is_current', type: 'boolean'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function sessions(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()->id;

        $sessions = $request->user()->tokens->map(function ($token) use ($currentTokenId) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'created_at' => $token->created_at->toIso8601String(),
                'is_current' => $token->id === $currentTokenId,
            ];
        });

        return response()->json([
            'success' => true,
            'sessions' => $sessions,
        ]);
    }

    #[OA\Delete(
        path: '/api/auth/sessions/{tokenId}',
        summary: 'Revocar una sesión específica',
        security: [['bearerAuth' => []]],
        tags: ['Autenticación'],
        parameters: [
            new OA\Parameter(
                name: 'tokenId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sesión revocada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Sesión no encontrada'),
        ]
    )]
    public function revokeSession(Request $request, int $tokenId): JsonResponse
    {
        $token = $request->user()->tokens()->find($tokenId);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión no encontrada',
            ], 404);
        }

        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión revocada exitosamente',
        ]);
    }

    #[OA\Post(
        path: '/api/auth/sessions/revoke-all',
        summary: 'Revocar todas las sesiones excepto la actual',
        security: [['bearerAuth' => []]],
        tags: ['Autenticación'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sesiones revocadas exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'revoked_count', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function revokeAllSessions(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()->id;

        $revokedCount = $request->user()
            ->tokens()
            ->where('id', '!=', $currentTokenId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Todas las demás sesiones han sido revocadas',
            'revoked_count' => $revokedCount,
        ]);
    }
}
