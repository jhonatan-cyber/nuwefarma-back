<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - NuweFarma (Sin Versionamiento)
|--------------------------------------------------------------------------
*/

// Info pública
Route::get('/info', function () {
    return response()->json([
        'name' => 'NuweFarma API',
        'version' => '1.0',
        'laravel_version' => app()->version(),
        'php_version' => PHP_VERSION,
        'documentation' => '/api/documentation',
    ]);
})->name('api.info');

// Login con rate limiting
Route::post('/auth/login', [\App\Http\Controllers\Api\AuthController::class, 'login'])
    ->middleware('throttle:login');

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (Autenticación requerida)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    
    // Auth
    Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/auth/me', [\App\Http\Controllers\Api\AuthController::class, 'me']);

    /*
    |--------------------------------------------------------------------------
    | Productos
    |--------------------------------------------------------------------------
    */
    Route::apiResource('productos', \App\Http\Controllers\Api\ProductoController::class)
        ->parameters(['productos' => 'producto']);
    
    Route::get('productos/bajo-stock', [\App\Http\Controllers\Api\ProductoController::class, 'bajoStock'])
        ->name('productos.bajo-stock');
    
    Route::get('productos/proximos-vencer', [\App\Http\Controllers\Api\ProductoController::class, 'proximosVencer'])
        ->name('productos.proximos-vencer');
    
    Route::post('productos/bulk-update', [\App\Http\Controllers\Api\ProductoController::class, 'bulkUpdate'])
        ->middleware('throttle:bulk')
        ->name('productos.bulk-update');

    /*
    |--------------------------------------------------------------------------
    | Categorías
    |--------------------------------------------------------------------------
    */
    Route::apiResource('categorias', \App\Http\Controllers\Api\CategoriaController::class);
    
    Route::get('categorias/{categoria}/productos', [\App\Http\Controllers\Api\CategoriaController::class, 'productos'])
        ->name('categorias.productos');

    /*
    |--------------------------------------------------------------------------
    | Proveedores
    |--------------------------------------------------------------------------
    */
    Route::apiResource('proveedores', \App\Http\Controllers\Api\ProveedorController::class);

    /*
    |--------------------------------------------------------------------------
    | Ventas
    |--------------------------------------------------------------------------
    */
    Route::apiResource('ventas', \App\Http\Controllers\Api\VentaController::class);
    
    Route::post('ventas/{venta}/completar', [\App\Http\Controllers\Api\VentaController::class, 'completar'])
        ->name('ventas.completar');
    
    Route::get('ventas/pendientes', [\App\Http\Controllers\Api\VentaController::class, 'pendientes'])
        ->name('ventas.pendientes');
    
    Route::get('ventas/por-fecha', [\App\Http\Controllers\Api\VentaController::class, 'porFecha'])
        ->name('ventas.por-fecha');

    /*
    |--------------------------------------------------------------------------
    | Clientes
    |--------------------------------------------------------------------------
    */
    Route::apiResource('clientes', \App\Http\Controllers\Api\ClienteController::class);

    /*
    |--------------------------------------------------------------------------
    | Usuarios
    |--------------------------------------------------------------------------
    */
    Route::apiResource('usuarios', \App\Http\Controllers\Api\UsuarioController::class);

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    */
    Route::apiResource('roles', \App\Http\Controllers\Api\RolController::class);

    /*
    |--------------------------------------------------------------------------
    | Sucursales
    |--------------------------------------------------------------------------
    */
    Route::apiResource('sucursales', \App\Http\Controllers\Api\SucursalController::class);

    /*
    |--------------------------------------------------------------------------
    | Compras
    |--------------------------------------------------------------------------
    */
    Route::apiResource('compras', \App\Http\Controllers\Api\CompraController::class);

    /*
    |--------------------------------------------------------------------------
    | Inventario
    |--------------------------------------------------------------------------
    */
    Route::get('inventario/resumen', [\App\Http\Controllers\Api\InventarioController::class, 'resumen'])
        ->name('inventario.resumen');
    
    Route::get('inventario/movimientos', [\App\Http\Controllers\Api\InventarioController::class, 'movimientos'])
        ->name('inventario.movimientos');

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'general'])
        ->name('dashboard.general');

    /*
    |--------------------------------------------------------------------------
    | Health Check
    |--------------------------------------------------------------------------
    */
    Route::get('health', [\App\Http\Controllers\Api\HealthController::class, 'check'])
        ->name('health.check');
});
