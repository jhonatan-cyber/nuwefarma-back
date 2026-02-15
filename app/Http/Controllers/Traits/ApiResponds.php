<?php

namespace App\Http\Controllers\Traits;

use App\Services\ApiResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponds
{
    /**
     * Return a success JSON response.
     */
    protected function success($data = null, string $message = 'Operación exitosa', int $status = 200): JsonResponse
    {
        return ApiResponseService::success($data, $message, $status);
    }

    /**
     * Return a created (201) JSON response.
     */
    protected function created($data = null, string $message = 'Recurso creado exitosamente'): JsonResponse
    {
        return ApiResponseService::created($data, $message);
    }

    /**
     * Return an error JSON response.
     */
    protected function error(string $message = 'Error en la operación', $errors = null, int $status = 400): JsonResponse
    {
        return ApiResponseService::error($message, $errors, $status);
    }

    /**
     * Return a validation error (422) JSON response.
     */
    protected function validationError($errors, string $message = 'Error de validación'): JsonResponse
    {
        return ApiResponseService::validationError($errors, $message);
    }

    /**
     * Return a not found (404) JSON response.
     */
    protected function notFound(string $message = 'Recurso no encontrado'): JsonResponse
    {
        return ApiResponseService::notFound($message);
    }

    /**
     * Return an unauthorized (401) JSON response.
     */
    protected function unauthorized(string $message = 'No autorizado'): JsonResponse
    {
        return ApiResponseService::unauthorized($message);
    }

    /**
     * Return a forbidden (403) JSON response.
     */
    protected function forbidden(string $message = 'Acceso prohibido'): JsonResponse
    {
        return ApiResponseService::forbidden($message);
    }

    /**
     * Return a conflict (409) JSON response.
     */
    protected function conflict(string $message = 'Conflicto en la operación'): JsonResponse
    {
        return ApiResponseService::conflict($message);
    }

    /**
     * Return a no content (204) JSON response.
     */
    protected function noContent(string $message = 'Operación exitosa'): JsonResponse
    {
        return ApiResponseService::noContent($message);
    }

    /**
     * Return a success response with paginated data.
     */
    protected function paginate(LengthAwarePaginator $paginator, string $message = 'Operación exitosa'): JsonResponse
    {
        return ApiResponseService::success(
            new ResourceCollection($paginator),
            $message
        );
    }

    /**
     * Return a success response with a single resource.
     */
    protected function respondWithResource(JsonResource $resource, string $message = 'Operación exitosa'): JsonResponse
    {
        return ApiResponseService::success($resource, $message);
    }
}
