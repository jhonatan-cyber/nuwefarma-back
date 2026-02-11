<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ValidateApiHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Validate required headers
        $errors = $this->validateHeaders($request);

        if (!empty($errors)) {
            Log::warning('API headers validation failed', [
                'errors' => $errors,
                'headers' => $request->headers->all(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid API headers',
                'errors' => $errors,
            ], 400);
        }

        // Add security headers
        $response = $next($request);
        
        $this->addSecurityHeaders($response);
        $this->addCachingHeaders($response, $request);
        $this->addApiVersionHeaders($response);

        return $response;
    }

    private function validateHeaders(Request $request): array
    {
        $errors = [];

        // Check Content-Type for POST/PUT/PATCH requests
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $contentType = $request->header('Content-Type');
            
            if (!$contentType || !str_contains($contentType, 'application/json')) {
                $errors['content_type'] = 'Content-Type must be application/json';
            }
        }

        // Check Accept header
        $accept = $request->header('Accept');
        if ($accept && !str_contains($accept, 'application/json')) {
            $errors['accept'] = 'Accept header must include application/json';
        }

        // Validate API version if provided
        $apiVersion = $request->header('X-API-Version');
        if ($apiVersion && !in_array($apiVersion, ['1', '2', 'v1', 'v2'])) {
            $errors['api_version'] = 'Invalid API version. Supported: v1, v2';
        }

        // Check for required custom headers
        $requiredHeaders = [
            'X-Request-ID' => 'string|max:100',
            'X-Client-Version' => 'string|max:50',
        ];

        foreach ($requiredHeaders as $header => $rule) {
            $value = $request->header($header);
            
            if (!$value) {
                $errors[$header] = "Header {$header} is required";
            } elseif (!$this->validateHeaderValue($value, $rule)) {
                $errors[$header] = "Header {$header} is invalid";
            }
        }

        return $errors;
    }

    private function validateHeaderValue(string $value, string $rule): bool
    {
        $rules = explode('|', $rule);
        
        foreach ($rules as $rule) {
            if (str_starts_with($rule, 'max:')) {
                $maxLength = (int) substr($rule, 4);
                if (strlen($value) > $maxLength) {
                    return false;
                }
            } elseif ($rule === 'string') {
                if (!is_string($value)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function addSecurityHeaders(Response $response): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none';");
    }

    private function addCachingHeaders(Response $response, Request $request): void
    {
        // Add cache headers based on response status and method
        $statusCode = $response->getStatusCode();
        $method = $request->method();

        if ($method === 'GET' && $statusCode === 200) {
            // Cache successful GET requests
            $response->headers->set('Cache-Control', 'public, max-age=300');
            $response->headers->set('ETag', md5($response->getContent()));
        } elseif (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            // Don't cache write operations
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        } else {
            // Default caching for other requests
            $response->headers->set('Cache-Control', 'no-cache');
        }
    }

    private function addApiVersionHeaders(Response $response): void
    {
        $response->headers->set('X-API-Version', '2.0');
        $response->headers->set('X-Laravel-Version', app()->version());
        $response->headers->set('X-PHP-Version', PHP_VERSION);
        $response->headers->set('X-Response-Time', microtime(true) - LARAVEL_START);
    }
}
