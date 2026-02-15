<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApiException extends Exception
{
    /**
     * Create a new API exception instance.
     *
     * @param  array<string, mixed>  $errors
     */
    public function __construct(
        string $message = 'API Error',
        public array $errors = [],
        int $code = Response::HTTP_BAD_REQUEST,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => $this->errors,
            'timestamp' => now()->toISOString(),
            'path' => $request->path(),
        ], $this->getCode());
    }

    /**
     * Create a validation error exception.
     *
     * @param  array<string, mixed>  $errors
     */
    public static function validation(array $errors): static
    {
        return new static(
            'Datos inválidos',
            $errors,
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    /**
     * Create a not found exception.
     */
    public static function notFound(string $resource, string $id): static
    {
        return new static(
            sprintf('%s no encontrado', $resource),
            ['id' => [$id]],
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Create an unauthorized exception.
     */
    public static function unauthorized(string $message = 'No autorizado'): static
    {
        return new static(
            $message,
            ['auth' => [$message]],
            Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * Create a forbidden exception.
     */
    public static function forbidden(string $message = 'Acceso denegado'): static
    {
        return new static(
            $message,
            ['permission' => [$message]],
            Response::HTTP_FORBIDDEN
        );
    }

    /**
     * Create a conflict exception.
     *
     * @param  array<string, mixed>  $errors
     */
    public static function conflict(string $message, array $errors = []): static
    {
        return new static(
            $message,
            $errors,
            Response::HTTP_CONFLICT
        );
    }
}
