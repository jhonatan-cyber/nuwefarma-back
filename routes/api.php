<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V2\ProductoController;
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
| API Routes - Categories
|--------------------------------------------------------------------------
|
| Category CRUD operations with role-based authorization
|
*/

Route::apiResource('categorias', \App\Http\Controllers\Api\CategoriaController::class)
    ->middleware(['auth:sanctum', 'role:Administrador,Gerente']);

// Additional category routes with specific role requirements
Route::prefix('categorias')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/bulk-update', [\App\Http\Controllers\Api\CategoriaController::class, 'bulkUpdate'])
        ->middleware('role:Administrador,Gerente');
    
    Route::get('/', [\App\Http\Controllers\Api\CategoriaController::class, 'index'])
        ->middleware('role:Administrador,Gerente,Vendedor,Almacenero');
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

Route::prefix('v2')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Product Routes - Advanced Laravel 12+ Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('productos')->group(function () {
        // Standard CRUD with advanced features
        Route::apiResource('productos', ProductoController::class)->parameters([
            'productos' => 'producto'
        ]);

        // Advanced search and filtering
        Route::get('/productos/advanced-search', [ProductoController::class, 'advancedSearch'])
            ->name('productos.advanced-search');
        
        Route::get('/productos/analytics', [ProductoController::class, 'analytics'])
            ->name('productos.analytics');
        
        Route::get('/productos/real-time-inventory', [ProductoController::class, 'realTime-inventory'])
            ->name('productos.real-time-inventory');

        // Product recommendations and AI features
        Route::get('/productos/{producto}/recommendations', [ProductoController::class, 'recommendations'])
            ->name('productos.recommendations');
        
        Route::get('/productos/ai-suggestions', [ProductoController::class, 'aiSuggestions'])
            ->name('productos.ai-suggestions');

        // Bulk operations
        Route::post('/productos/bulk-update-stock', [ProductoController::class, 'bulkUpdateStock'])
            ->name('productos.bulk-update-stock');

        // Advanced filtering with JSON
        Route::post('/productos/filter-by-json', [ProductoController::class, 'filterByJson'])
            ->name('productos.filter-by-json');

        // State machine operations
        Route::patch('/productos/{producto}/toggle-status', [ProductoController::class, 'toggleStatus'])
            ->name('productos.toggle-status');
        
        Route::get('/productos/{producto}/state-transitions', [ProductoController::class, 'getStateTransitions'])
            ->name('productos.state-transitions');

        // Stock management
        Route::patch('/productos/{producto}/update-stock', [ProductoController::class, 'updateStock'])
            ->name('productos.update-stock');

        // Export functionality
        Route::post('/productos/export', [ProductoController::class, 'export'])
            ->name('productos.export');

        // Nested resources
        Route::prefix('/productos/{producto}')->group(function () {
            Route::get('/lotes', [ProductoLoteController::class, 'index'])
                ->name('productos.lotes.index');
            
            Route::get('/historial', [ProductoHistorialController::class, 'index'])
                ->name('productos.historial');
            
            Route::get('/movimientos', [ProductoMovimientoController::class, 'index'])
                ->name('productos.movimientos');
            
            Route::get('/imagenes', [ProductoImagenController::class, 'index'])
                ->name('productos.imagenes.index');
            
            Route::post('/imagenes', [ProductoImagenController::class, 'store'])
                ->name('productos.imagenes.store');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Category Routes - Advanced Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('categorias')->group(function () {
        Route::apiResource('categorias', CategoriaController::class);
        
        Route::get('/categorias/{categoria}/productos', [CategoriaController::class, 'productos'])
            ->name('categorias.productos');
        
        Route::get('/categorias/{categoria}/analytics', [CategoriaController::class, 'analytics'])
            ->name('categorias.analytics');
        
        Route::get('/categorias/tree', [CategoriaController::class, 'tree'])
            ->name('categorias.tree');
    });

    /*
    |--------------------------------------------------------------------------
    | Provider Routes - Advanced Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('proveedores')->group(function () {
        Route::apiResource('proveedores', ProveedorController::class);
        
        Route::get('/proveedores/{proveedor}/productos', [ProveedorController::class, 'productos'])
            ->name('proveedores.productos');
        
        Route::get('/proveedores/{proveedor}/analytics', [ProveedorController::class, 'analytics'])
            ->name('proveedores.analytics');
        
        Route::get('/proveedores/{proveedor}/ordenes', [ProveedorController::class, 'ordenes'])
            ->name('proveedores.ordenes');
    });

    /*
    |--------------------------------------------------------------------------
    | Sales Routes - Advanced Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('ventas')->group(function () {
        Route::apiResource('ventas', VentaController::class);
        
        Route::get('/ventas/analytics', [VentaController::class, 'analytics'])
            ->name('ventas.analytics');
        
        Route::get('/ventas/dashboard', [VentaController::class, 'dashboard'])
            ->name('ventas.dashboard');
        
        Route::post('/ventas/{venta}/anular', [VentaController::class, 'anular'])
            ->name('ventas.anular');
        
        Route::post('/ventas/{venta}/devolucion', [VentaController::class, 'devolucion'])
            ->name('ventas.devolucion');
    });

    /*
    |--------------------------------------------------------------------------
    | Inventory Routes - Advanced Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('inventario')->group(function () {
        Route::get('/inventario/resumen', [InventarioController::class, 'resumen'])
            ->name('inventario.resumen');
        
        Route::get('/inventario/movimientos', [InventarioController::class, 'movimientos'])
            ->name('inventario.movimientos');
        
        Route::get('/inventario/ajustes', [InventarioController::class, 'ajustes'])
            ->name('inventario.ajustes');
        
        Route::post('/inventario/ajustes', [InventarioController::class, 'crearAjuste'])
            ->name('inventario.ajustes.store');
        
        Route::get('/inventario/reportes', [InventarioController::class, 'reportes'])
            ->name('inventario.reportes');
        
        Route::get('/inventario/alertas', [InventarioController::class, 'alertas'])
            ->name('inventario.alertas');
    });

    /*
    |--------------------------------------------------------------------------
    | Reports Routes - Advanced Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('reportes')->group(function () {
        Route::get('/reportes/inventario', [ReporteController::class, 'inventario'])
            ->name('reportes.inventario');
        
        Route::get('/reportes/ventas', [ReporteController::class, 'ventas'])
            ->name('reportes.ventas');
        
        Route::get('/reportes/financiero', [ReporteController::class, 'financiero'])
            ->name('reportes.financiero');
        
        Route::post('/reportes/generar', [ReporteController::class, 'generar'])
            ->name('reportes.generar');
        
        Route::get('/reportes/{reporte}/descargar', [ReporteController::class, 'descargar'])
            ->name('reportes.descargar');
    });

    /*
    |--------------------------------------------------------------------------
    | Dashboard Routes - Advanced Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('dashboard')->group(function () {
        Route::get('/dashboard/general', [DashboardController::class, 'general'])
            ->name('dashboard.general');
        
        Route::get('/dashboard/ventas', [DashboardController::class, 'ventas'])
            ->name('dashboard.ventas');
        
        Route::get('/dashboard/inventario', [DashboardController::class, 'inventario'])
            ->name('dashboard.inventario');
        
        Route::get('/dashboard/financiero', [DashboardController::class, 'financiero'])
            ->name('dashboard.financiero');
        
        Route::get('/dashboard/alertas', [DashboardController::class, 'alertas'])
            ->name('dashboard.alertas');
    });

    /*
    |--------------------------------------------------------------------------
    | System Routes - Advanced Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('system')->group(function () {
        Route::get('/system/health', [SystemController::class, 'health'])
            ->name('system.health');
        
        Route::get('/system/metrics', [SystemController::class, 'metrics'])
            ->name('system.metrics');
        
        Route::get('/system/performance', [SystemController::class, 'performance'])
            ->name('system.performance');
        
        Route::get('/system/logs', [SystemController::class, 'logs'])
            ->name('system.logs');
        
        Route::post('/system/cache/clear', [SystemController::class, 'clearCache'])
            ->name('system.cache.clear');
    });

    /*
    |--------------------------------------------------------------------------
    | AI Routes - Laravel 12+ AI Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('ai')->group(function () {
        Route::post('/ai/chat', [AIController::class, 'chat'])
            ->name('ai.chat');
        
        Route::post('/ai/analyze', [AIController::class, 'analyze'])
            ->name('ai.analyze');
        
        Route::post('/ai/predict', [AIController::class, 'predict'])
            ->name('ai.predict');
        
        Route::post('/ai/recommend', [AIController::class, 'recommend'])
            ->name('ai.recommend');
    });

    /*
    |--------------------------------------------------------------------------
    | Webhook Routes - Laravel 12+ Webhook Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('webhooks')->group(function () {
        Route::post('/webhooks/stripe', [WebhookController::class, 'stripe'])
            ->name('webhooks.stripe');
        
        Route::post('/webhooks/github', [WebhookController::class, 'github'])
            ->name('webhooks.github');
        
        Route::post('/webhooks/custom', [WebhookController::class, 'custom'])
            ->name('webhooks.custom');
    });

    /*
    |--------------------------------------------------------------------------
    | Real-time Routes - Laravel 12+ Broadcasting Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('realtime')->group(function () {
        Route::get('/realtime/notifications', [RealtimeController::class, 'notifications'])
            ->name('realtime.notifications');
        
        Route::post('/realtime/broadcast', [RealtimeController::class, 'broadcast'])
            ->name('realtime.broadcast');
        
        Route::get('/realtime/channels', [RealtimeController::class, 'channels'])
            ->name('realtime.channels');
    });
});

/*
|--------------------------------------------------------------------------
| API Version Information
|--------------------------------------------------------------------------
*/
Route::get('/api/info', function () {
    return response()->json([
        'name' => 'NuweFarma API',
        'version' => '2.0',
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
            'v1' => '/api/v1',
            'v2' => '/api/v2',
        ],
        'documentation' => [
            'swagger' => '/api/documentation',
            'postman' => '/api/postman',
        ],
    ]);
})->name('api.info');
