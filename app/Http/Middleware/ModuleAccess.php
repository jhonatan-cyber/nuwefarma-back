<?php

namespace App\Http\Middleware;

use App\Services\ApiResponseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModuleAccess
{
    private const INVENTORY_MODULES = [
        'proveedores', 'compras', 'lotes', 'kardex', 'traslados',
        'ajustes-inventario', 'notificaciones', 'pdf', 'dashboard',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum');

        if (! $user) {
            return $next($request);
        }

        $module = $request->segment(3);
        $role = $user->rol?->nombre;

        if ($this->isAllowed($role, $module, $request->method())) {
            return $next($request);
        }

        return ApiResponseService::forbidden('No tiene permisos para acceder a este módulo');
    }

    private function isAllowed(?string $role, ?string $module, string $method): bool
    {
        if (in_array($module, ['auth', 'info', 'health'], true)) {
            return true;
        }

        if (in_array($role, ['Administrador', 'Gerente'], true)) {
            return true;
        }

        if (in_array($role, ['Cajero', 'Vendedor'], true)) {
            if (in_array($module, ['productos', 'categorias'], true)) {
                return in_array($method, ['GET', 'HEAD'], true);
            }

            if (in_array($module, ['dashboard', 'pdf'], true)) {
                return in_array($method, ['GET', 'HEAD'], true);
            }

            if (in_array($module, ['clientes', 'ventas', 'cotizaciones'], true)) {
                return $method !== 'DELETE';
            }

            if ($module === 'cajas') {
                return in_array($method, ['GET', 'HEAD', 'PATCH'], true);
            }

            return $module === 'notificaciones';
        }

        if ($role === 'Almacenero') {
            if ($module === 'categorias') {
                return in_array($method, ['GET', 'HEAD'], true);
            }

            return $module === 'productos' || in_array($module, self::INVENTORY_MODULES, true);
        }

        return false;
    }
}
