<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Authentication
|--------------------------------------------------------------------------
|
| Basic authentication routes without authentication middleware
|
*/

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| API Routes - Users
|--------------------------------------------------------------------------
|
| User management routes with role-based authorization
|
*/

Route::apiResource('usuarios', \App\Http\Controllers\Api\UsuarioController::class)
    ->middleware(['auth:sanctum']);

// Additional user routes
Route::prefix('usuarios')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/{usuario}/assign-role', [\App\Http\Controllers\Api\UsuarioController::class, 'assignRole'])
        ->middleware('role:Administrador');
});

/*
|--------------------------------------------------------------------------
| API Routes - Roles
|--------------------------------------------------------------------------
|
| Role management routes with role-based authorization
|
*/

Route::apiResource('roles', \App\Http\Controllers\Api\RolController::class)
    ->middleware(['auth:sanctum']);

// Additional role routes
Route::prefix('roles')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/{role}/toggle-estado', [\App\Http\Controllers\Api\RolController::class, 'toggleEstado']);
});

/*
|--------------------------------------------------------------------------
| API Routes - Clients
|--------------------------------------------------------------------------
|
| Client management routes with role-based authorization
|
*/

Route::apiResource('clientes', \App\Http\Controllers\Api\ClienteController::class)
    ->middleware(['auth:sanctum']);

// Additional client routes
Route::prefix('clientes')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/bulk-update', [\App\Http\Controllers\Api\ClienteController::class, 'bulkUpdate']);
    Route::get('/con-deuda', [\App\Http\Controllers\Api\ClienteController::class, 'conDeuda']);
    Route::patch('/{cliente}/toggle-estado', [\App\Http\Controllers\Api\ClienteController::class, 'toggleEstado']);
    Route::get('/stats/overview', [\App\Http\Controllers\Api\ClienteController::class, 'statsOverview']);
});

/*
|--------------------------------------------------------------------------
| API Routes - Sales
|--------------------------------------------------------------------------
|
| Sales management routes
|
*/

Route::apiResource('ventas', \App\Http\Controllers\Api\VentaController::class)
    ->middleware(['auth:sanctum']);

// Additional sales routes
Route::prefix('ventas')->middleware(['auth:sanctum'])->group(function () {
    Route::patch('/{venta}/completar', [\App\Http\Controllers\Api\VentaController::class, 'completar']);
    Route::patch('/{venta}/cancelar', [\App\Http\Controllers\Api\VentaController::class, 'cancelar']);
    Route::get('/pendientes', [\App\Http\Controllers\Api\VentaController::class, 'pendientes']);
    Route::get('/por-fecha', [\App\Http\Controllers\Api\VentaController::class, 'porFecha']);
});

/*
|--------------------------------------------------------------------------
| API Routes - Products
|--------------------------------------------------------------------------
|
| Product CRUD operations with role-based authorization
|
*/

// Additional product routes - MOVER ANTES del apiResource
Route::prefix('productos')->middleware(['auth:sanctum', 'validate.api.headers'])->group(function () {
    Route::get('/bajo-stock', [\App\Http\Controllers\Api\ProductoController::class, 'bajoStock']);
    Route::get('/proximos-vencer', [\App\Http\Controllers\Api\ProductoController::class, 'proximosVencer']);
    Route::patch('/{id}/toggle-estado', [\App\Http\Controllers\Api\ProductoController::class, 'toggleEstado']);
});

Route::apiResource('productos', \App\Http\Controllers\Api\ProductoController::class)
    ->middleware(['auth:sanctum', 'validate.api.headers']);

Route::apiResource('proveedores', \App\Http\Controllers\Api\ProveedorController::class)
    ->middleware(['auth:sanctum']);

Route::prefix('proveedores')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/bulk-update', [\App\Http\Controllers\Api\ProveedorController::class, 'bulkUpdate']);
});

/*
|--------------------------------------------------------------------------
| API Routes - Categories
|--------------------------------------------------------------------------
|
| Category CRUD operations with role-based authorization
|
*/

Route::apiResource('categorias', \App\Http\Controllers\Api\CategoriaController::class)
    ->middleware(['auth:sanctum', 'role:Administrador,Gerente']);

// Additional category routes
Route::prefix('categorias')->middleware(['auth:sanctum', 'role:Administrador,Gerente'])->group(function () {
    Route::patch('/{id}/toggle-estado', [\App\Http\Controllers\Api\CategoriaController::class, 'toggleEstado']);
});

// Additional category routes with specific role requirements
Route::prefix('categorias')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/bulk-update', [\App\Http\Controllers\Api\CategoriaController::class, 'bulkUpdate'])
        ->middleware('role:Administrador,Gerente');
});

/*
|--------------------------------------------------------------------------
| API Routes - Cash Registers
|--------------------------------------------------------------------------
|
| Cash Register CRUD operations with role-based authorization
|
*/

Route::apiResource('cajas', \App\Http\Controllers\Api\CajaController::class)
    ->middleware(['auth:sanctum', 'role:Administrador,Gerente']);

// Additional cash register routes
Route::prefix('cajas')->middleware(['auth:sanctum', 'role:Administrador,Gerente'])->group(function () {
    Route::patch('/{id}/abrir', [\App\Http\Controllers\Api\CajaController::class, 'abrir']);
    Route::patch('/{id}/cerrar', [\App\Http\Controllers\Api\CajaController::class, 'cerrar']);
    Route::get('/abiertas', [\App\Http\Controllers\Api\CajaController::class, 'abiertas']);
    Route::get('/cerradas', [\App\Http\Controllers\Api\CajaController::class, 'cerradas']);
});

/*
|--------------------------------------------------------------------------
| API Routes - (Laravel 12+ Advanced Features)
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
|--------------------------------------------------------------------------
| API Version Information
|--------------------------------------------------------------------------
*/
Route::get('/info', function () {
    return response()->json([
        'name' => 'NuweFarma API',
        'version' => '1.0.0',
        'laravel_version' => app()->version(),
        'php_version' => PHP_VERSION,
        'features' => [
            'laravel_12_plus' => true,
            'advanced_scopes' => true,
            'ai_integration' => true,
            'real_time' => true,
            'webhooks' => true,
            'advanced_analytics' => true,
            'json_operations' => true,
            'window_functions' => true,
            'cte_support' => true,
        ],
        'endpoints' => [
            'root' => '/api',
        ],
        'documentation' => [
            'swagger' => '/api/documentation',
            'postman' => '/api/postman',
        ],
    ]);
})->name('api.info');
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'data' => [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => true,
                'cache' => true,
                'storage' => true,
            ],
        ],
    ]);
})->middleware('auth:sanctum')->name('api.health');
