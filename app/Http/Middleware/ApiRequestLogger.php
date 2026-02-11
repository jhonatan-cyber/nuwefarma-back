<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiRequestLogger
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        // Log the incoming request
        $this->logRequest($request);

        $response = $next($request);

        // Log the response
        $this->logResponse($request, $response, $startTime);

        return $response;
    }

    /**
     * Log the incoming request
     */
    private function logRequest(Request $request): void
    {
        $data = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'headers' => $this->getImportantHeaders($request),
        ];

        // Log request body only for specific methods and in non-production environments
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH']) && !app()->environment('production')) {
            $data['body'] = $request->except(['password', 'password_confirmation']);
        }

        Log::channel('api')->info('API Request', $data);
    }

    /**
     * Log the response
     */
    private function logResponse(Request $request, $response, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $data = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
        ];

        // Log slow requests
        if ($duration > 1000) {
            $data['slow_request'] = true;
            Log::channel('api')->warning('Slow API Request', $data);
        } else {
            Log::channel('api')->info('API Response', $data);
        }

        // Log error responses
        if ($response->getStatusCode() >= 400) {
            Log::channel('api')->error('API Error Response', array_merge($data, [
                'response_body' => $response->getContent(),
            ]));
        }
    }

    /**
     * Get important headers for logging
     */
    private function getImportantHeaders(Request $request): array
    {
        $importantHeaders = [
            'accept',
            'accept-encoding',
            'accept-language',
            'authorization',
            'content-type',
            'content-length',
            'origin',
            'referer',
            'x-forwarded-for',
            'x-forwarded-proto',
            'x-requested-with',
        ];

        $headers = [];
        foreach ($importantHeaders as $header) {
            $value = $request->header($header);
            if ($value !== null) {
                // Mask sensitive headers
                if ($header === 'authorization') {
                    $value = 'Bearer [MASKED]';
                }
                $headers[$header] = $value;
            }
        }

        return $headers;
    }
}
