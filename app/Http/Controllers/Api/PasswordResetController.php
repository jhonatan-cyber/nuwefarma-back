<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Recuperación de Contraseña', description: 'Operaciones para recuperar/resetear contraseña')]
class PasswordResetController extends Controller
{
    #[OA\Post(
        path: '/api/auth/forgot-password',
        summary: 'Solicitar token de recuperación de contraseña',
        tags: ['Recuperación de Contraseña'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan.perez@example.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token de recuperación generado (en desarrollo se devuelve el token, en producción se enviaría por email)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'token', type: 'string', example: 'abc123...', description: 'Solo en desarrollo'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
        ]
    )]
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $usuario = Usuario::where('email', $request->email)->first();

            if (! $usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró un usuario con ese email.',
                ], 404);
            }

            // Generar token único
            $token = Str::random(60);

            // Eliminar tokens anteriores del usuario
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            // Guardar nuevo token (válido por 1 hora)
            DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => Hash::make($token),
                'created_at' => now(),
                'expires_at' => now()->addHour(),
            ]);

            // Registrar actividad
            ActivityLog::registrar(
                accion: 'forgot_password',
                modulo: 'auth',
                descripcion: "Solicitud de recuperación de contraseña para: {$request->email}",
                usuarioId: $usuario->id
            );

            // En producción, aquí se enviaría un email con el token
            // Por ahora, devolvemos el token en la respuesta para desarrollo
            return response()->json([
                'success' => true,
                'message' => 'Se ha generado un token de recuperación. En producción se enviaría por email.',
                'token' => $token, // Solo para desarrollo
                'expires_in' => '1 hora',
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
        path: '/api/auth/reset-password',
        summary: 'Resetear contraseña con token',
        tags: ['Recuperación de Contraseña'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'token', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan.perez@example.com'),
                    new OA\Property(property: 'token', type: 'string', example: 'abc123...'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'nueva-clave-123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'nueva-clave-123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Contraseña reseteada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Token inválido o expirado'),
        ]
    )]
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'token' => 'required|string',
                'password' => 'required|string|min:6|confirmed',
            ]);

            // Buscar token
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (! $resetRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de recuperación no encontrado.',
                ], 400);
            }

            // Verificar token
            if (! Hash::check($request->token, $resetRecord->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido.',
                ], 400);
            }

            // Verificar expiración
            if (now()->gt($resetRecord->expires_at)) {
                DB::table('password_reset_tokens')->where('email', $request->email)->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'El token ha expirado. Solicita uno nuevo.',
                ], 400);
            }

            $usuario = Usuario::where('email', $request->email)->first();

            if (! $usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado.',
                ], 404);
            }

            // Actualizar contraseña directamente (sin depender del CI)
            $usuario->password = Hash::make($request->password);
            $usuario->save();

            // Revocar todos los tokens de sesión
            $usuario->tokens()->delete();

            // Eliminar token de recuperación
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            // Registrar actividad
            ActivityLog::registrar(
                accion: 'password_reset',
                modulo: 'auth',
                descripcion: "Contraseña reseteada exitosamente para: {$request->email}",
                usuarioId: $usuario->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente. Por favor, inicia sesión con tu nueva contraseña.',
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
        path: '/api/auth/change-password',
        summary: 'Cambiar contraseña del usuario autenticado',
        security: [['bearerAuth' => []]],
        tags: ['Recuperación de Contraseña'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['password_actual', 'nueva_password'],
                properties: [
                    new OA\Property(property: 'password_actual', type: 'string', format: 'password', example: 'clave-actual'),
                    new OA\Property(property: 'nueva_password', type: 'string', format: 'password', example: 'nueva-clave-123'),
                    new OA\Property(property: 'nueva_password_confirmation', type: 'string', format: 'password', example: 'nueva-clave-123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Contraseña cambiada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'La contraseña actual es incorrecta'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'password_actual' => 'required|string',
                'nueva_password' => 'required|string|min:6|confirmed',
            ]);

            /** @var Usuario $usuario */
            $usuario = $request->user();

            if (! Hash::check($request->password_actual, $usuario->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta.',
                ], 400);
            }

            // La nueva contraseña no puede ser igual a la actual
            if (Hash::check($request->nueva_password, $usuario->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La nueva contraseña debe ser diferente a la actual.',
                ], 400);
            }

            $usuario->password = Hash::make($request->nueva_password);
            $usuario->save();

            // Revocar las demás sesiones, conservando la sesión actual
            $usuario->tokens()
                ->where('id', '!=', $request->user()->currentAccessToken()?->id)
                ->delete();

            // Registrar actividad
            ActivityLog::registrar(
                accion: 'change_password',
                modulo: 'auth',
                descripcion: "El usuario {$usuario->email} cambió su contraseña",
                usuarioId: $usuario->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Contraseña cambiada exitosamente.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
