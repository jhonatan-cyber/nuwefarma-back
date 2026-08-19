<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeApiResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->is('api/v1/*') || ! $response instanceof JsonResponse || $response->getStatusCode() === 204) {
            return $response;
        }

        $status = $response->getStatusCode();
        $payload = $response->getData(true);
        $requestId = $request->attributes->get('request_id');

        if (! is_array($payload) || ! array_key_exists('success', $payload)) {
            $payload = $status < 400
                ? [
                    'success' => true,
                    'message' => 'Operación exitosa',
                    'data' => $payload,
                ]
                : [
                    'success' => false,
                    'code' => $this->errorCode($status),
                    'message' => Response::$statusTexts[$status] ?? 'Error en la operación',
                    'errors' => $payload,
                ];
        }

        $payload['message'] ??= $payload['success'] ? 'Operación exitosa' : 'Error en la operación';
        $payload['request_id'] ??= $requestId;

        if ($payload['success'] === false) {
            $payload['code'] ??= $this->errorCode($status);
        }

        $response->setData($payload);

        return $response;
    }

    private function errorCode(int $status): string
    {
        return match ($status) {
            401 => 'UNAUTHENTICATED',
            403 => 'FORBIDDEN',
            404 => 'RESOURCE_NOT_FOUND',
            409 => 'CONFLICT',
            422 => 'VALIDATION_ERROR',
            429 => 'RATE_LIMIT_EXCEEDED',
            default => $status >= 500 ? 'INTERNAL_ERROR' : 'BAD_REQUEST',
        };
    }
}
