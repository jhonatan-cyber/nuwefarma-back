<?php

namespace App\Jobs\Middleware;

use Illuminate\Support\Facades\RateLimiter;

class RateLimitReportes
{
    /**
     * Process the queued job.
     */
    public function handle(object $job, callable $next): void
    {
        // Limitar a 5 reportes por hora por usuario
        $key = 'reportes:'.$job->usuarioId;

        if (RateLimiter::tooManyAttempts($key, 5, 3600)) {
            // Si excede el límite, reintentar en 1 hora
            $job->release(3600);

            \Log::warning('Límite de generación de reportes excedido', [
                'usuario_id' => $job->usuarioId,
                'key' => $key,
            ]);

            return;
        }

        RateLimiter::hit($key, 3600);

        $next($job);
    }
}
