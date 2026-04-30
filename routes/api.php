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
Route::prefix('productos')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/bajo-stock', [\App\Http\Controllers\Api\ProductoController::class, 'bajoStock']);
    Route::get('/proximos-vencer', [\App\Http\Controllers\Api\ProductoController::class, 'proximosVencer']);
    Route::patch('/{id}/toggle-estado', [\App\Http\Controllers\Api\ProductoController::class, 'toggleEstado']);
});

Route::apiResource('productos', \App\Http\Controllers\Api\ProductoController::class)
    ->middleware(['auth:sanctum']);

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
    
    Route::get('/', [\App\Http\Controllers\Api\CategoriaController::class, 'index'])
        ->middleware('role:Administrador,Gerente,Vendedor,Almacenero');
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

Route::prefix('v2')->middleware(['api', 'auth:sanctum', 'throttle:api'])->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Product Routes - Advanced Laravel 12+ Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('productos')->group(function () {
        // Standard CRUD with advanced features
        Route::apiResource('productos', \App\Http\Controllers\Api\ProductoController::class)->parameters([
            'productos' => 'producto'
        ]);

        // Advanced search and filtering
        Route::get('/productos/advanced-search', [\App\Http\Controllers\Api\ProductoController::class, 'advancedSearch'])
            ->name('productos.advanced-search');
        
        Route::get('/productos/analytics', [\App\Http\Controllers\Api\ProductoController::class, 'analytics'])
            ->name('productos.analytics');
        
        Route::get('/productos/real-time-inventory', [\App\Http\Controllers\Api\ProductoController::class, 'realTime-inventory'])
            ->name('productos.real-time-inventory');

        // Product recommendations and AI features
        Route::get('/productos/{producto}/recommendations', [\App\Http\Controllers\Api\ProductoController::class, 'recommendations'])
            ->name('productos.recommendations');
        
        Route::get('/productos/ai-suggestions', [\App\Http\Controllers\Api\ProductoController::class, 'aiSuggestions'])
            ->name('productos.ai-suggestions');

        // Bulk operations
        Route::post('/productos/bulk-update-stock', [\App\Http\Controllers\Api\ProductoController::class, 'bulkUpdateStock'])
            ->name('productos.bulk-update-stock');

        // Advanced filtering with JSON
        Route::post('/productos/filter-by-json', [\App\Http\Controllers\Api\ProductoController::class, 'filterByJson'])
            ->name('productos.filter-by-json');

        // State machine operations
        Route::patch('/productos/{producto}/toggle-status', [\App\Http\Controllers\Api\ProductoController::class, 'toggleStatus'])
            ->name('productos.toggle-status');
        
        Route::get('/productos/{producto}/state-transitions', [\App\Http\Controllers\Api\ProductoController::class, 'getStateTransitions'])
            ->name('productos.state-transitions');

        // Stock management
        Route::patch('/productos/{producto}/update-stock', [\App\Http\Controllers\Api\ProductoController::class, 'updateStock'])
            ->name('productos.update-stock');

        // Export functionality
        Route::post('/productos/export', [\App\Http\Controllers\Api\ProductoController::class, 'export'])
            ->name('productos.export');

        // Nested resources
        Route::prefix('/productos/{producto}')->group(function () {
            Route::get('/lotes', [\App\Http\Controllers\Api\LoteController::class, 'index'])
                ->name('productos.lotes.index');
            
            Route::get('/movimientos', [\App\Http\Controllers\Api\KardexController::class, 'index'])
                ->name('productos.movimientos');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Category Routes - Advanced Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('categorias')->group(function () {
        Route::apiResource('categorias', \App\Http\Controllers\Api\CategoriaController::class);
        
        Route::get('/categorias/{categoria}/productos', [\App\Http\Controllers\Api\CategoriaController::class, 'productos'])
            ->name('categorias.productos');
        
        Route::get('/categorias/{categoria}/analytics', [\App\Http\Controllers\Api\CategoriaController::class, 'analytics'])
            ->name('categorias.analytics');
        
        Route::get('/categorias/tree', [\App\Http\Controllers\Api\CategoriaController::class, 'tree'])
            ->name('categorias.tree');
    });

    /*
    |--------------------------------------------------------------------------
    | Provider Routes - Advanced Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('proveedores')->group(function () {
        Route::apiResource('proveedores', \App\Http\Controllers\Api\ProveedorController::class);
        
        Route::get('/proveedores/{proveedor}/productos', [\App\Http\Controllers\Api\ProveedorController::class, 'productos'])
            ->name('proveedores.productos');
        
        Route::get('/proveedores/{proveedor}/analytics', [\App\Http\Controllers\Api\ProveedorController::class, 'analytics'])
            ->name('proveedores.analytics');
        
        Route::get('/proveedores/{proveedor}/ordenes', [\App\Http\Controllers\Api\ProveedorController::class, 'ordenes'])
            ->name('proveedores.ordenes');
    });

    /*
    |--------------------------------------------------------------------------
    | Sales Routes - Advanced Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('ventas')->group(function () {
        Route::apiResource('ventas', \App\Http\Controllers\Api\VentaController::class);
        
        Route::get('/ventas/analytics', [\App\Http\Controllers\Api\VentaController::class, 'analytics'])
            ->name('ventas.analytics');
        
        Route::get('/ventas/dashboard', [\App\Http\Controllers\Api\VentaController::class, 'dashboard'])
            ->name('ventas.dashboard');
        
        Route::post('/ventas/{venta}/anular', [\App\Http\Controllers\Api\VentaController::class, 'anular'])
            ->name('ventas.anular');
        
        Route::post('/ventas/{venta}/devolucion', [\App\Http\Controllers\Api\VentaController::class, 'devolucion'])
            ->name('ventas.devolucion');
    });

    /*
    |--------------------------------------------------------------------------
    | Inventory Routes - Advanced Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('inventario')->group(function () {
        Route::get('/inventario/resumen', [\App\Http\Controllers\Api\DashboardController::class, 'resumen'])
            ->name('inventario.resumen');
        
        Route::get('/inventario/movimientos', [\App\Http\Controllers\Api\KardexController::class, 'movimientos'])
            ->name('inventario.movimientos');
        
        Route::get('/inventario/ajustes', [\App\Http\Controllers\Api\AjusteInventarioController::class, 'ajustes'])
            ->name('inventario.ajustes');
        
        Route::post('/inventario/ajustes', [\App\Http\Controllers\Api\AjusteInventarioController::class, 'crearAjuste'])
            ->name('inventario.ajustes.store');
        
        Route::get('/inventario/reportes', [\App\Http\Controllers\Api\DashboardController::class, 'reportes'])
            ->name('inventario.reportes');
        
        Route::get('/inventario/alertas', [\App\Http\Controllers\Api\NotificacionController::class, 'alertas'])
            ->name('inventario.alertas');
    });

    /*
    |--------------------------------------------------------------------------
    | Reports Routes - Advanced Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('reportes')->group(function () {
        Route::get('/reportes/inventario', [\App\Http\Controllers\Api\DashboardController::class, 'inventario'])
            ->name('reportes.inventario');

        Route::get('/reportes/ventas', [\App\Http\Controllers\Api\DashboardController::class, 'ventas'])
            ->name('reportes.ventas');

        Route::get('/reportes/financiero', [\App\Http\Controllers\Api\DashboardController::class, 'financiero'])
            ->name('reportes.financiero');

        Route::post('/reportes/generar', [\App\Http\Controllers\Api\PdfController::class, 'generar'])
            ->name('reportes.generar');

        Route::get('/reportes/{reporte}/descargar', [\App\Http\Controllers\Api\PdfController::class, 'descargar'])
            ->name('reportes.descargar');

        // PDF Routes
        Route::get('/reportes/ventas/pdf', [\App\Http\Controllers\Api\PdfController::class, 'reporteVentas'])
            ->name('reportes.ventas.pdf');

        Route::get('/reportes/compras/pdf', [\App\Http\Controllers\Api\PdfController::class, 'reporteCompras'])
            ->name('reportes.compras.pdf');

        Route::get('/reportes/inventario/pdf', [\App\Http\Controllers\Api\PdfController::class, 'reporteInventario'])
            ->name('reportes.inventario.pdf');

        Route::get('/reportes/kardex/pdf/{loteId}', [\App\Http\Controllers\Api\PdfController::class, 'reporteKardex'])
            ->name('reportes.kardex.pdf');

        Route::get('/reportes/stock-bajo/pdf', [\App\Http\Controllers\Api\PdfController::class, 'reporteStockBajo'])
            ->name('reportes.stock-bajo.pdf');

        Route::get('/reportes/proximos-vencer/pdf', [\App\Http\Controllers\Api\PdfController::class, 'reporteProximosVencer'])
            ->name('reportes.proximos-vencer.pdf');

        Route::get('/reportes/movimientos/pdf', [\App\Http\Controllers\Api\PdfController::class, 'reporteMovimientos'])
            ->name('reportes.movimientos.pdf');

        Route::get('/reportes/cotizacion/pdf', [\App\Http\Controllers\Api\PdfController::class, 'reporteCotizacion'])
            ->name('reportes.cotizacion.pdf');
    });

    // PDF Routes for comprobantes
    Route::get('/ventas/{id}/comprobante/pdf', [\App\Http\Controllers\Api\PdfController::class, 'comprobanteVenta'])
        ->name('ventas.comprobante.pdf');

    Route::get('/compras/{id}/comprobante/pdf', [\App\Http\Controllers\Api\PdfController::class, 'comprobanteCompra'])
        ->name('compras.comprobante.pdf');

    /*
    |--------------------------------------------------------------------------
    | Dashboard Routes - Advanced Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('dashboard')->group(function () {
        Route::get('/dashboard/general', [\App\Http\Controllers\Api\DashboardController::class, 'general'])
            ->name('dashboard.general');
        
        Route::get('/dashboard/ventas', [\App\Http\Controllers\Api\DashboardController::class, 'ventas'])
            ->name('dashboard.ventas');
        
        Route::get('/dashboard/inventario', [\App\Http\Controllers\Api\DashboardController::class, 'inventario'])
            ->name('dashboard.inventario');
        
        Route::get('/dashboard/financiero', [\App\Http\Controllers\Api\DashboardController::class, 'financiero'])
            ->name('dashboard.financiero');
        
        Route::get('/dashboard/alertas', [\App\Http\Controllers\Api\NotificacionController::class, 'alertas'])
            ->name('dashboard.alertas');
    });

    /*
    |--------------------------------------------------------------------------
    | System Routes - Advanced Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('system')->group(function () {
        Route::get('/system/health', [\App\Http\Controllers\Api\HealthController::class, 'health'])
            ->name('system.health');
        
        Route::get('/system/metrics', [\App\Http\Controllers\Api\HealthController::class, 'metrics'])
            ->name('system.metrics');
        
        Route::get('/system/performance', [\App\Http\Controllers\Api\HealthController::class, 'performance'])
            ->name('system.performance');
        
        Route::get('/system/logs', [\App\Http\Controllers\Api\HealthController::class, 'logs'])
            ->name('system.logs');
        
        Route::post('/system/cache/clear', [\App\Http\Controllers\Api\HealthController::class, 'clearCache'])
            ->name('system.cache.clear');
    });

    /*
    |--------------------------------------------------------------------------
    | AI Routes - Laravel 12+ AI Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('ai')->group(function () {
        Route::post('/ai/chat', [\App\Http\Controllers\Api\DashboardController::class, 'chat'])
            ->name('ai.chat');
        
        Route::post('/ai/analyze', [\App\Http\Controllers\Api\DashboardController::class, 'analyze'])
            ->name('ai.analyze');
        
        Route::post('/ai/predict', [\App\Http\Controllers\Api\DashboardController::class, 'predict'])
            ->name('ai.predict');
        
        Route::post('/ai/recommend', [\App\Http\Controllers\Api\DashboardController::class, 'recommend'])
            ->name('ai.recommend');
    });

    /*
    |--------------------------------------------------------------------------
    | Webhook Routes - Laravel 12+ Webhook Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('webhooks')->group(function () {
        Route::post('/webhooks/stripe', [\App\Http\Controllers\Api\AuthController::class, 'stripe'])
            ->name('webhooks.stripe');
        
        Route::post('/webhooks/github', [\App\Http\Controllers\Api\AuthController::class, 'github'])
            ->name('webhooks.github');
        
        Route::post('/webhooks/custom', [\App\Http\Controllers\Api\AuthController::class, 'custom'])
            ->name('webhooks.custom');
    });

    /*
    |--------------------------------------------------------------------------
    | Real-time Routes - Laravel 12+ Broadcasting Features
    |--------------------------------------------------------------------------
    */
    Route::prefix('realtime')->group(function () {
        Route::get('/realtime/notifications', [\App\Http\Controllers\Api\NotificacionController::class, 'notifications'])
            ->name('realtime.notifications');
        
        Route::post('/realtime/broadcast', [\App\Http\Controllers\Api\NotificacionController::class, 'broadcast'])
            ->name('realtime.broadcast');
        
        Route::get('/realtime/channels', [\App\Http\Controllers\Api\NotificacionController::class, 'channels'])
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
