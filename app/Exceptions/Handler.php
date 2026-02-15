<?php

namespace App\Exceptions;

use App\Services\ApiResponseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function __construct(
        private readonly ApiResponseService $apiResponse
    ) {
        parent::__construct();
    }

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if ($this->shouldReport($e)) {
                $this->logException($e);
            }
        });

        $this->renderable(function (Throwable $e, Request $request) {
            return $this->handleApiException($e, $request);
        });
    }

    public function render($request, Throwable $e): \Illuminate\Http\Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($e, $request);
        }

        return parent::render($request, $e);
    }

    private function handleApiException(Throwable $e, Request $request): JsonResponse
    {
        // Handle specific exception types
        return match (true) {
            $e instanceof ValidationException => $this->handleValidationException($e),
            $e instanceof ModelNotFoundException => $this->handleModelNotFoundException($e),
            $e instanceof NotFoundHttpException => $this->handleNotFoundHttpException($e),
            $e instanceof MethodNotAllowedHttpException => $this->handleMethodNotAllowedHttpException($e),
            $e instanceof UnauthorizedHttpException => $this->handleUnauthorizedHttpException($e),
            $e instanceof AccessDeniedHttpException => $this->handleAccessDeniedHttpException($e),
            $e instanceof HttpException => $this->handleHttpException($e),
            default => $this->handleGenericException($e, $request),
        };
    }

    private function handleValidationException(ValidationException $e): JsonResponse
    {
        return $this->apiResponse->validationError(
            $e->errors(),
            'Error de validación en los datos enviados'
        );
    }

    private function handleModelNotFoundException(ModelNotFoundException $e): JsonResponse
    {
        $model = class_basename($e->getModel());

        return $this->apiResponse->notFound(
            strtolower($model).' no encontrado'
        );
    }

    private function handleNotFoundHttpException(NotFoundHttpException $e): JsonResponse
    {
        return $this->apiResponse->notFound('Recurso no encontrado');
    }

    private function handleMethodNotAllowedHttpException(MethodNotAllowedHttpException $e): JsonResponse
    {
        $allowedMethods = $e->getHeaders()['Allow'] ?? 'GET, POST, PUT, PATCH, DELETE';

        return $this->apiResponse->error(
            'Método HTTP no permitido para este endpoint',
            ['allowed_methods' => explode(', ', $allowedMethods)],
            405
        );
    }

    private function handleUnauthorizedHttpException(UnauthorizedHttpException $e): JsonResponse
    {
        return $this->apiResponse->unauthorized(
            $e->getMessage() ?: 'No autorizado para acceder a este recurso'
        );
    }

    private function handleAccessDeniedHttpException(AccessDeniedHttpException $e): JsonResponse
    {
        return $this->apiResponse->forbidden(
            $e->getMessage() ?: 'Acceso prohibido a este recurso'
        );
    }

    private function handleHttpException(HttpException $e): JsonResponse
    {
        return $this->apiResponse->error(
            $e->getMessage() ?: 'Error HTTP',
            [],
            $e->getStatusCode()
        );
    }

    private function handleGenericException(Throwable $e, Request $request): JsonResponse
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
                'request_id' => $request->header('X-Request-ID'),
            ];
        }

        return $this->apiResponse->error(
            $message,
            $details,
            500
        );
    }

    private function logException(Throwable $e): void
    {
        $context = [
            'exception' => $e,
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'request_id' => request()->header('X-Request-ID'),
        ];

        // Log based on exception severity
        match (true) {
            $e instanceof ValidationException => logger()->warning('Validation error', $context),
            $e instanceof ModelNotFoundException => logger()->warning('Model not found', $context),
            $e instanceof NotFoundHttpException => logger()->warning('Route not found', $context),
            $e instanceof UnauthorizedHttpException => logger()->warning('Unauthorized access', $context),
            $e instanceof AccessDeniedHttpException => logger()->warning('Access denied', $context),
            default => logger()->error('Unhandled exception', $context),
        };
    }

    protected function shouldReport(Throwable $e): bool
    {
        // Don't report certain exceptions in production
        $dontReport = [
            ValidationException::class,
            NotFoundHttpException::class,
            MethodNotAllowedHttpException::class,
        ];

        if (app()->environment('production') && in_array(get_class($e), $dontReport)) {
            return false;
        }

        return parent::shouldReport($e);
    }
}
