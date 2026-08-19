<?php

namespace App\Http\Middleware;

use App\Services\ApiResponseService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSucursalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum');

        if (! $user || $user->isAdmin()) {
            return $next($request);
        }

        $requestedSucursal = $request->input('sucursal_id');

        if ($requestedSucursal && $requestedSucursal !== $user->sucursal_id) {
            return ApiResponseService::forbidden('No puede operar sobre otra sucursal');
        }

        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if ($parameter instanceof Model && $this->belongsToAnotherSucursal($parameter, $user->sucursal_id)) {
                return ApiResponseService::forbidden('No puede acceder a recursos de otra sucursal');
            }
        }

        return $next($request);
    }

    private function belongsToAnotherSucursal(Model $model, ?string $sucursalId): bool
    {
        if (array_key_exists('sucursal_id', $model->getAttributes())) {
            return ! $sucursalId || $model->getAttribute('sucursal_id') !== $sucursalId;
        }

        return false;
    }
}
