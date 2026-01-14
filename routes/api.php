<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ProductoStatsController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\RolController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\GastoController;
use App\Http\Controllers\Api\SucursalController;
use App\Http\Controllers\Api\CotizacionController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

// Rutas públicas de autenticación
Route::prefix('/auth')->group(function () {
    // Rate limiting: máximo 5 intentos por minuto
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    // Recuperación de contraseña
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])
        ->middleware('throttle:3,1'); // Máximo 3 intentos por minuto
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:3,1');
});

// Rutas protegidas con autenticación
Route::middleware(['auth:sanctum', 'verify.origin', 'verify.ua', 'api.ratelimit'])->group(function () {
    // Auth
    Route::prefix('/auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/sessions', [AuthController::class, 'sessions']);
        Route::delete('/sessions/{tokenId}', [AuthController::class, 'revokeSession']);
        Route::post('/sessions/revoke-all', [AuthController::class, 'revokeAllSessions']);
    });

    // Rutas de roles (solo admin puede crearlas/editarlas)
    Route::prefix('/roles')->group(function () {
        Route::get('/', [RolController::class, 'index']);
        Route::get('/{id}', [RolController::class, 'show']);
        Route::get('/{id}/usuarios-count', [RolController::class, 'getUsuariosCount']);

        // Protegidas: solo Administrador
        Route::middleware('role:Administrador')->group(function () {
            Route::post('/', [RolController::class, 'store']);
            Route::put('/{id}', [RolController::class, 'update']);
            Route::delete('/{id}', [RolController::class, 'destroy']);
            Route::patch('/{id}/toggle-estado', [RolController::class, 'toggleEstado']);
        });
    });

    // Rutas de usuarios (solo admin puede crearlos/editarlos)
    Route::prefix('/usuarios')->group(function () {
        Route::get('/', [UsuarioController::class, 'index']);
        Route::get('/{id}', [UsuarioController::class, 'show']);

        // Protegidas: solo Administrador
        Route::middleware('role:Administrador')->group(function () {
            Route::post('/', [UsuarioController::class, 'store']);
            Route::put('/{id}', [UsuarioController::class, 'update']);
            Route::delete('/{id}', [UsuarioController::class, 'destroy']);
            Route::patch('/{id}/toggle-estado', [UsuarioController::class, 'toggleEstado']);
        });
    });

    // Rutas de logs de actividad (solo lectura)
    Route::prefix('/activity-logs')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index']);
        Route::get('/me', [ActivityLogController::class, 'myLogs']);
        Route::get('/{id}', [ActivityLogController::class, 'show']);
    });

    // Rutas de categorías (solo admin puede crear/editar/eliminar)
    Route::prefix('/categorias')->group(function () {
        Route::get('/', [CategoriaController::class, 'index']);
        Route::get('/{id}', [CategoriaController::class, 'show']);

        // Protegidas: solo Administrador
        Route::middleware('role:Administrador')->group(function () {
            Route::post('/', [CategoriaController::class, 'store']);
            Route::put('/{id}', [CategoriaController::class, 'update']);
            Route::delete('/{id}', [CategoriaController::class, 'destroy']);
            Route::patch('/{id}/toggle-estado', [CategoriaController::class, 'toggleEstado']);
        });
    });

    // Rutas de productos (solo admin puede crear/editar/eliminar)
    Route::prefix('/productos')->group(function () {
        Route::get('/', [ProductoController::class, 'index']);
        Route::get('/{id}', [ProductoController::class, 'show']);
        // Stats de productos (solo lectura)
        Route::get('/stats/overview', [ProductoStatsController::class, 'overview']);

        // Protegidas: solo Administrador
        Route::middleware('role:Administrador')->group(function () {
            Route::post('/', [ProductoController::class, 'store']);
            Route::put('/{id}', [ProductoController::class, 'update']);
            Route::patch('/{id}', [ProductoController::class, 'update']);
            Route::delete('/{id}', [ProductoController::class, 'destroy']);
            Route::patch('/{id}/toggle-estado', [ProductoController::class, 'toggleEstado']);
            // Subida de imágenes de producto
            Route::post('/upload-image', [ProductoController::class, 'uploadImage']);
        });
    });

    // Rutas de proveedores (solo admin puede crear/editar/eliminar)
    Route::prefix('/proveedores')->group(function () {
        Route::get('/', [ProveedorController::class, 'index']);
        Route::get('/{id}', [ProveedorController::class, 'show']);

        // Protegidas: solo Administrador
        Route::middleware('role:Administrador')->group(function () {
            Route::post('/', [ProveedorController::class, 'store']);
            Route::put('/{id}', [ProveedorController::class, 'update']);
            Route::delete('/{id}', [ProveedorController::class, 'destroy']);
            Route::patch('/{id}/toggle-estado', [ProveedorController::class, 'toggleEstado']);
        });
    });

    // Rutas de gastos (solo admin puede crear/editar/eliminar)
    Route::prefix('/gastos')->group(function () {
        Route::get('/', [GastoController::class, 'index']);
        Route::get('/{id}', [GastoController::class, 'show']);

        // Protegidas: solo Administrador
        Route::middleware('role:Administrador')->group(function () {
            Route::post('/', [GastoController::class, 'store']);
            Route::put('/{id}', [GastoController::class, 'update']);
            Route::patch('/{id}', [GastoController::class, 'update']);
            Route::delete('/{id}', [GastoController::class, 'destroy']);
            Route::patch('/{id}/toggle-estado', [GastoController::class, 'toggleEstado']);
        });
    });

    // Rutas de sucursales (solo admin puede crear/editar/eliminar)
    Route::prefix('/sucursales')->group(function () {
        Route::get('/', [SucursalController::class, 'index']);
        Route::get('/{sucursal}', [SucursalController::class, 'show']);

        // Protegidas: Administrador puede hacer todo, Gerentes pueden editar sus propias sucursales
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [SucursalController::class, 'store'])->middleware('role:Administrador');
            Route::put('/{sucursal}', [SucursalController::class, 'update']);
            Route::patch('/{sucursal}', [SucursalController::class, 'update']);
            Route::delete('/{sucursal}', [SucursalController::class, 'destroy'])->middleware('role:Administrador');
            Route::patch('/{sucursal}/toggle-estado', [SucursalController::class, 'toggleEstado'])->middleware('role:Administrador');
        });
    });

    // Rutas de cotizaciones (users autenticados pueden crear)
    Route::prefix('/cotizaciones')->group(function () {
        Route::get('/', [CotizacionController::class, 'index']);
        Route::get('/{id}', [CotizacionController::class, 'show']);

        // Protegidas: usuarios autenticados pueden crear/editar sus cotizaciones
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/', [CotizacionController::class, 'store']);
            Route::put('/{id}', [CotizacionController::class, 'update']);
            Route::patch('/{id}', [CotizacionController::class, 'update']);
            Route::delete('/{id}', [CotizacionController::class, 'destroy']);
            Route::patch('/{id}/cambiar-estado', [CotizacionController::class, 'cambiarEstado'])->middleware('role:Administrador');
        });
    });
});
