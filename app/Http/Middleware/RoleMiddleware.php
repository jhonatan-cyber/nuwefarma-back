<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request with role-based authorization.
     */
    public function handle(Request $request, Closure $next, string ...$roles): JsonResponse|Response
    {
        /** @var Usuario $user */
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
                'errors' => [
                    'auth' => ['Usuario no autenticado'],
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Laravel 12+: Enhanced role checking with pattern matching
        $userRole = $user->rol?->nombre;

        if (! $this->userHasRequiredRole($userRole, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado',
                'errors' => [
                    'role' => [
                        sprintf(
                            'Se requiere uno de estos roles: %s. Rol actual: %s',
                            implode(', ', $roles),
                            $userRole ?? 'sin rol'
                        ),
                    ],
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        // Laravel 12+: Add role context to request for downstream use
        $request->attributes->set('user_role', $userRole);
        $request->attributes->set('user_abilities', $this->getUserAbilities($userRole));

        return $next($request);
    }

    /**
     * Check if user has required role using Laravel 12+ pattern matching.
     *
     * @param  array<string>  $requiredRoles
     */
    private function userHasRequiredRole(?string $userRole, array $requiredRoles): bool
    {
        if (! $userRole) {
            return false;
        }

        return match (true) {
            in_array('*', $requiredRoles) => true,
            in_array($userRole, $requiredRoles) => true,
            $userRole === 'Administrador' => true, // Admins have access to everything
            default => false
        };
    }

    /**
     * Get user abilities based on role with Laravel 12+ pattern matching.
     *
     * @return array<string>
     */
    private function getUserAbilities(?string $role): array
    {
        return match ($role) {
            'Administrador' => ['*'],
            'Gerente' => ['users:read', 'users:write', 'products:*', 'categories:*', 'sales:*'],
            'Vendedor' => ['products:read', 'categories:read', 'sales:read', 'sales:write'],
            'Almacenero' => ['products:*', 'categories:read', 'inventory:*'],
            default => ['profile:read']
        };
    }
}
