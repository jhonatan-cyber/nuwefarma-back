<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\UserResource;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class AuthController extends Controller
{
    /**
     * Authenticate user and return token with Laravel 12+ features.
     * 
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = Usuario::where('email', $validated['email'])
            ->with(['rol'])
            ->firstOrFail();

        if (!Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if ($user->estado !== 'activo') {
            return response()->json([
                'success' => false,
                'message' => 'Usuario inactivo',
                'errors' => [
                    'email' => ['La cuenta de usuario está inactiva.']
                ]
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Laravel 12+: Enhanced token management with abilities
        $abilities = $this->getUserAbilities($user);
        $token = $user->createToken('auth-token', $abilities, now()->addDay());

        return response()->json([
            'success' => true,
            'message' => 'Login exitoso',
            'data' => [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_in' => now()->addDay()->diffInSeconds(now()),
                'abilities' => $abilities,
                'user' => new UserResource($user)
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Logout user and revoke token with Laravel 12+ token management.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Get authenticated user profile with Laravel 12+ eager loading.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['rol', 'sucursal']);
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'permissions' => $this->getUserAbilities($user)
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Refresh authentication token with Laravel 12+ enhanced security.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user()->load(['rol']);
        
        // Revoke all tokens for enhanced security
        $user->tokens()->delete();
        
        // Create new token with updated abilities
        $abilities = $this->getUserAbilities($user);
        $token = $user->createToken('auth-token', $abilities, now()->addDay());

        return response()->json([
            'success' => true,
            'message' => 'Token renovado exitosamente',
            'data' => [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_in' => now()->addDay()->diffInSeconds(now()),
                'abilities' => $abilities,
                'user' => new UserResource($user)
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Get user abilities based on role with Laravel 12+ pattern matching.
     * 
     * @param Usuario $user
     * @return array<string>
     */
    private function getUserAbilities(Usuario $user): array
    {
        return match ($user->rol->nombre) {
            'Administrador' => ['*'],
            'Gerente' => ['users:read', 'users:write', 'products:*', 'categories:*', 'sales:*'],
            'Vendedor' => ['products:read', 'categories:read', 'sales:read', 'sales:write'],
            'Almacenero' => ['products:*', 'categories:read', 'inventory:*'],
            default => ['profile:read']
        };
    }
}
