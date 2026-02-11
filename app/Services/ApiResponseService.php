<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponseService
{
    /**
     * Respuesta exitosa con datos
     */
    public static function success($data = null, string $message = 'Operación exitosa', int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            if ($data instanceof ResourceCollection || $data instanceof LengthAwarePaginator) {
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
            } elseif ($data instanceof JsonResource) {
                $response['data'] = $data;
            } else {
                $response['data'] = $data;
            }
        }

        return response()->json($response, $status);
    }

    /**
     * Respuesta de error
     */
    public static function error(string $message = 'Error en la operación', $errors = null, int $status = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Respuesta de validación
     */
    public static function validationError($errors, string $message = 'Error de validación'): JsonResponse
    {
        return self::error($message, $errors, 422);
    }

    /**
     * Respuesta de no encontrado
     */
    public static function notFound(string $message = 'Recurso no encontrado'): JsonResponse
    {
        return self::error($message, null, 404);
    }

    /**
     * Respuesta de no autorizado
     */
    public static function unauthorized(string $message = 'No autorizado'): JsonResponse
    {
        return self::error($message, null, 401);
    }

    /**
     * Respuesta de prohibido
     */
    public static function forbidden(string $message = 'Acceso prohibido'): JsonResponse
    {
        return self::error($message, null, 403);
    }

    /**
     * Respuesta de conflicto
     */
    public static function conflict(string $message = 'Conflicto en la operación'): JsonResponse
    {
        return self::error($message, null, 409);
    }

    /**
     * Respuesta de creado
     */
    public static function created($data = null, string $message = 'Recurso creado exitosamente'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * Respuesta sin contenido
     */
    public static function noContent(string $message = 'Operación exitosa'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], 204);
    }

    /**
     * Respuesta con metadatos adicionales
     */
    public static function successWithMeta($data, array $meta, string $message = 'Operación exitosa', int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ];

        return response()->json($response, $status);
    }
}
