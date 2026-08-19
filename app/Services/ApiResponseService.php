<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseService
{
    public static function success($data = null, string $message = 'Operación exitosa', int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'request_id' => self::requestId(),
        ];

        if ($data instanceof JsonResource) {
            $response['data'] = $data;
        } elseif ($data instanceof LengthAwarePaginator) {
            $response['data'] = $data->items();
            $response['meta'] = [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ];
            $response['links'] = [
                'first' => $data->url(1),
                'last' => $data->url($data->lastPage()),
                'prev' => $data->previousPageUrl(),
                'next' => $data->nextPageUrl(),
            ];
        } elseif ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    public static function error(
        string $message = 'Error en la operación',
        $errors = null,
        int $status = 400,
        ?string $code = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'code' => $code ?? self::defaultErrorCode($status),
            'message' => $message,
            'request_id' => self::requestId(),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    public static function validationError($errors, string $message = 'Error de validación'): JsonResponse
    {
        return self::error($message, $errors, 422, 'VALIDATION_ERROR');
    }

    public static function notFound(string $message = 'Recurso no encontrado'): JsonResponse
    {
        return self::error($message, null, 404, 'RESOURCE_NOT_FOUND');
    }

    public static function unauthorized(string $message = 'No autorizado'): JsonResponse
    {
        return self::error($message, null, 401, 'UNAUTHENTICATED');
    }

    public static function forbidden(string $message = 'Acceso prohibido'): JsonResponse
    {
        return self::error($message, null, 403, 'FORBIDDEN');
    }

    public static function conflict(string $message = 'Conflicto en la operación'): JsonResponse
    {
        return self::error($message, null, 409, 'CONFLICT');
    }

    public static function created($data = null, string $message = 'Recurso creado exitosamente'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    public static function noContent(string $message = 'Operación exitosa'): JsonResponse
    {
        return response()->json(null, 204, [
            'X-Request-ID' => self::requestId() ?? '',
        ]);
    }

    public static function successWithMeta(
        $data,
        array $meta,
        string $message = 'Operación exitosa',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
            'request_id' => self::requestId(),
        ], $status);
    }

    private static function requestId(): ?string
    {
        return app()->bound('request')
            ? request()->attributes->get('request_id')
            : null;
    }

    private static function defaultErrorCode(int $status): string
    {
        return match ($status) {
            Response::HTTP_UNAUTHORIZED => 'UNAUTHENTICATED',
            Response::HTTP_FORBIDDEN => 'FORBIDDEN',
            Response::HTTP_NOT_FOUND => 'RESOURCE_NOT_FOUND',
            Response::HTTP_CONFLICT => 'CONFLICT',
            Response::HTTP_UNPROCESSABLE_ENTITY => 'VALIDATION_ERROR',
            Response::HTTP_TOO_MANY_REQUESTS => 'RATE_LIMIT_EXCEEDED',
            default => $status >= 500 ? 'INTERNAL_ERROR' : 'BAD_REQUEST',
        };
    }
}
