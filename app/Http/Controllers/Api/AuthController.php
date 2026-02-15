<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\UserResource;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = Usuario::where('email', $validated['email'])
            ->with(['rol'])
            ->firstOrFail();

        if (! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if ($user->estado !== 'activo') {
            return $this->unauthorized('Usuario inactivo');
        }

        $abilities = $this->getUserAbilities($user);
        $token = $user->createToken('auth-token', $abilities, now()->addDay());

        return $this->success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => 86400,
            'abilities' => $abilities,
            'user' => new UserResource($user),
        ], 'Login exitoso');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Sesión cerrada exitosamente');
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['rol', 'sucursal']);

        return $this->success([
            'user' => new UserResource($user),
            'permissions' => $this->getUserAbilities($user),
        ]);
    }

    public function refresh(Request $request)
    {
        $user = $request->user()->load(['rol']);

        $user->tokens()->delete();

        $abilities = $this->getUserAbilities($user);
        $token = $user->createToken('auth-token', $abilities, now()->addDay());

        return $this->success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => 86400,
            'abilities' => $abilities,
            'user' => new UserResource($user),
        ], 'Token renovado exitosamente');
    }

    private function getUserAbilities(Usuario $user): array
    {
        $roleName = $user->rol->nombre;
        $rolesConfig = config('roles.roles');

        if (isset($rolesConfig[$roleName])) {
            return $rolesConfig[$roleName]['abilities'];
        }

        return config('roles.default.abilities', ['profile:read']);
    }
}
