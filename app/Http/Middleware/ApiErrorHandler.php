<?php

namespace App\Http\Middleware;

use App\Services\ApiResponseService;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class ApiErrorHandler
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): JsonResponse
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    /**
     * Handle exceptions and return appropriate API response
     */
    private function handleException(Throwable $e, Request $request): JsonResponse
    {
        // Log the exception
        \Log::error('API Error: '.$e->getMessage(), [
            'exception' => $e,
            'request' => $request->all(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Handle specific exception types
        return match (true) {
            $e instanceof ValidationException => $this->handleValidationException($e),
            $e instanceof ModelNotFoundException => $this->handleModelNotFoundException($e),
            $e instanceof NotFoundHttpException => $this->handleNotFoundHttpException($e),
            $e instanceof MethodNotAllowedHttpException => $this->handleMethodNotAllowedHttpException($e),
            $e instanceof UnauthorizedHttpException => $this->handleUnauthorizedHttpException($e),
            $e instanceof AccessDeniedHttpException => $this->handleAccessDeniedHttpException($e),
            default => $this->handleGenericException($e),
        };
    }

    /**
     * Handle validation exceptions
     */
    private function handleValidationException(ValidationException $e): JsonResponse
    {
        return ApiResponseService::validationError(
            $e->errors(),
            'Error de validación en los datos enviados'
        );
    }

    /**
     * Handle model not found exceptions
     */
    private function handleModelNotFoundException(ModelNotFoundException $e): JsonResponse
    {
        $model = class_basename($e->getModel());

        return ApiResponseService::notFound(
            strtolower($model).' no encontrado'
        );
    }

    /**
     * Handle HTTP not found exceptions
     */
    private function handleNotFoundHttpException(NotFoundHttpException $e): JsonResponse
    {
        return ApiResponseService::notFound('Recurso no encontrado');
    }

    /**
     * Handle method not allowed exceptions
     */
    private function handleMethodNotAllowedHttpException(MethodNotAllowedHttpException $e): JsonResponse
    {
        return ApiResponseService::error(
            'Método HTTP no permitido para este endpoint',
            ['allowed_methods' => $e->getHeaders()['Allow'] ?? []],
            405
        );
    }

    /**
     * Handle unauthorized exceptions
     */
    private function handleUnauthorizedHttpException(UnauthorizedHttpException $e): JsonResponse
    {
        return ApiResponseService::unauthorized(
            $e->getMessage() ?: 'No autorizado para acceder a este recurso'
        );
    }

    /**
     * Handle access denied exceptions
     */
    private function handleAccessDeniedHttpException(AccessDeniedHttpException $e): JsonResponse
    {
        return ApiResponseService::forbidden(
            $e->getMessage() ?: 'Acceso prohibido a este recurso'
        );
    }

    /**
     * Handle generic exceptions
     */
    private function handleGenericException(Throwable $e): JsonResponse
    {
        // Don't expose internal details in production
        $shouldExposeDetails = app()->environment(['local', 'testing', 'staging']);

        $message = 'Error interno del servidor';
        $details = null;

        if ($shouldExposeDetails) {
            $message = $e->getMessage();
            $details = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(10)->toArray(),
            ];
        }

        return ApiResponseService::error(
            $message,
            $details,
            500
        );
    }
}
