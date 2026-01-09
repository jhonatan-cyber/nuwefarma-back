<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\RolController;
use App\Http\Controllers\Api\UsuarioController;
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
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::prefix('/auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/sessions', [AuthController::class, 'sessions']);
        Route::delete('/sessions/{tokenId}', [AuthController::class, 'revokeSession']);
        Route::post('/sessions/revoke-all', [AuthController::class, 'revokeAllSessions']);
    });

    // Rutas de roles
    Route::prefix('/roles')->group(function () {
        Route::get('/', [RolController::class, 'index']);
        Route::post('/', [RolController::class, 'store']);
        Route::get('/{id}', [RolController::class, 'show']);
        Route::put('/{id}', [RolController::class, 'update']);
        Route::delete('/{id}', [RolController::class, 'destroy']);
        Route::patch('/{id}/toggle-estado', [RolController::class, 'toggleEstado']);
    });

    // Rutas de usuarios
    Route::prefix('/usuarios')->group(function () {
        Route::get('/', [UsuarioController::class, 'index']);
        Route::post('/', [UsuarioController::class, 'store']);
        Route::get('/{id}', [UsuarioController::class, 'show']);
        Route::put('/{id}', [UsuarioController::class, 'update']);
        Route::delete('/{id}', [UsuarioController::class, 'destroy']);
        Route::patch('/{id}/toggle-estado', [UsuarioController::class, 'toggleEstado']);
    });

    // Rutas de logs de actividad
    Route::prefix('/activity-logs')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index']);
        Route::get('/me', [ActivityLogController::class, 'myLogs']);
        Route::get('/{id}', [ActivityLogController::class, 'show']);
    });

    // Rutas de categorías
    Route::prefix('/categorias')->group(function () {
        Route::get('/', [CategoriaController::class, 'index']);
        Route::post('/', [CategoriaController::class, 'store']);
        Route::get('/{id}', [CategoriaController::class, 'show']);
        Route::put('/{id}', [CategoriaController::class, 'update']);
        Route::delete('/{id}', [CategoriaController::class, 'destroy']);
        Route::patch('/{id}/toggle-estado', [CategoriaController::class, 'toggleEstado']);
    });

    // Rutas de productos
    Route::prefix('/productos')->group(function () {
        Route::get('/', [ProductoController::class, 'index']);
        Route::post('/', [ProductoController::class, 'store']);
        Route::get('/{id}', [ProductoController::class, 'show']);
        Route::put('/{id}', [ProductoController::class, 'update']);
        Route::delete('/{id}', [ProductoController::class, 'destroy']);
        Route::patch('/{id}/toggle-estado', [ProductoController::class, 'toggleEstado']);
    });

    // Rutas de proveedores
    Route::prefix('/proveedores')->group(function () {
        Route::get('/', [ProveedorController::class, 'index']);
        Route::post('/', [ProveedorController::class, 'store']);
        Route::get('/{id}', [ProveedorController::class, 'show']);
        Route::put('/{id}', [ProveedorController::class, 'update']);
        Route::delete('/{id}', [ProveedorController::class, 'destroy']);
        Route::patch('/{id}/toggle-estado', [ProveedorController::class, 'toggleEstado']);
    });
});

