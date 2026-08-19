<?php

namespace App\Providers;

use App\Models\Cliente;
use App\Observers\ClienteObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register application bindings here when a concrete implementation exists.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerObservers();
        $this->configureRateLimiting();
    }

    /**
     * Register model observers.
     */
    protected function registerObservers(): void
    {
        Cliente::observe(ClienteObserver::class);
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Rate limit global para API: 60 solicitudes por minuto
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limit estricto para login: 5 intentos por minuto
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Rate limit para reset de password: 3 intentos por minuto
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        // Rate limit para reportes: 10 solicitudes por minuto
        RateLimiter::for('reportes', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limit para exportación: 5 solicitudes por minuto
        RateLimiter::for('export', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limit para operaciones bulk: 10 solicitudes por minuto
        RateLimiter::for('bulk', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
