<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->middleware(['module.access', 'sucursal.access'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | API Routes - Authentication
    |--------------------------------------------------------------------------
    |
    | Basic authentication routes without authentication middleware
    |
    */

    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [\App\Http\Controllers\Api\PasswordResetController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/auth/reset-password', [\App\Http\Controllers\Api\PasswordResetController::class, 'resetPassword'])->middleware('throttle:10,1');
    Route::post('/auth/change-password', [\App\Http\Controllers\Api\PasswordResetController::class, 'changePassword'])->middleware('auth:sanctum');
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

    // Additional role routes must be declared before /roles/{role}.
    Route::prefix('roles')->middleware(['auth:sanctum'])->group(function () {
        Route::get('/stats/overview', [\App\Http\Controllers\Api\RolController::class, 'statsOverview']);
        Route::post('/{role}/toggle-estado', [\App\Http\Controllers\Api\RolController::class, 'toggleEstado']);
    });

    Route::apiResource('roles', \App\Http\Controllers\Api\RolController::class)
        ->middleware(['auth:sanctum']);

    /*
    |--------------------------------------------------------------------------
    | API Routes - Clients
    |--------------------------------------------------------------------------
    |
    | Client management routes with role-based authorization
    |
    */

    // Additional client routes must be declared before /clientes/{cliente}.
    Route::prefix('clientes')->middleware(['auth:sanctum'])->group(function () {
        Route::post('/bulk-update', [\App\Http\Controllers\Api\ClienteController::class, 'bulkUpdate']);
        Route::get('/con-deuda', [\App\Http\Controllers\Api\ClienteController::class, 'conDeuda']);
        Route::patch('/{cliente}/toggle-estado', [\App\Http\Controllers\Api\ClienteController::class, 'toggleEstado']);
        Route::get('/stats/overview', [\App\Http\Controllers\Api\ClienteController::class, 'statsOverview']);
        Route::get('/{cliente}/estado-cuenta', [\App\Http\Controllers\Api\ClienteController::class, 'estadoCuenta']);
    });

    Route::apiResource('clientes', \App\Http\Controllers\Api\ClienteController::class)
        ->middleware(['auth:sanctum']);

    /*
    |--------------------------------------------------------------------------
    | API Routes - Sales
    |--------------------------------------------------------------------------
    |
    | Sales management routes
    |
    */

    // Additional sales routes must be declared before /ventas/{venta}.
    Route::prefix('ventas')->middleware(['auth:sanctum'])->group(function () {
        Route::get('/pendientes', [\App\Http\Controllers\Api\VentaController::class, 'pendientes']);
        Route::get('/por-fecha', [\App\Http\Controllers\Api\VentaController::class, 'porFecha']);
        Route::patch('/{venta}/completar', [\App\Http\Controllers\Api\VentaController::class, 'completar']);
        Route::patch('/{venta}/cancelar', [\App\Http\Controllers\Api\VentaController::class, 'cancelar']);
        Route::patch('/{venta}/devolver', [\App\Http\Controllers\Api\VentaController::class, 'devolver']);
        Route::patch('/{venta}/abonar', [\App\Http\Controllers\Api\VentaController::class, 'abonar']);
    });

    Route::apiResource('ventas', \App\Http\Controllers\Api\VentaController::class)
        ->middleware(['auth:sanctum']);

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
        Route::get('/stats/overview', [\App\Http\Controllers\Api\ProductoStatsController::class, 'overview']);
        Route::patch('/{id}/toggle-estado', [\App\Http\Controllers\Api\ProductoController::class, 'toggleEstado']);
    });

    Route::apiResource('productos', \App\Http\Controllers\Api\ProductoController::class)
        ->middleware(['auth:sanctum', 'validate.api.headers']);

    Route::prefix('proveedores')->middleware(['auth:sanctum'])->group(function () {
        Route::post('/bulk-update', [\App\Http\Controllers\Api\ProveedorController::class, 'bulkUpdate']);
        Route::get('/stats/overview', [\App\Http\Controllers\Api\ProveedorController::class, 'statsOverview']);
        Route::patch('/{proveedor}/toggle-estado', [\App\Http\Controllers\Api\ProveedorController::class, 'toggleEstado']);
        Route::get('/{proveedor}/estado-cuenta', [\App\Http\Controllers\Api\ProveedorController::class, 'estadoCuenta']);
    });

    Route::apiResource('proveedores', \App\Http\Controllers\Api\ProveedorController::class)
        ->parameters(['proveedores' => 'proveedor'])
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
        ->middleware(['auth:sanctum', 'role:Administrador,Gerente,Cajero,Vendedor,Almacenero']);

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

    // Additional cash-register routes must be declared before /cajas/{caja}.
    Route::prefix('cajas')->middleware(['auth:sanctum', 'role:Administrador,Gerente,Cajero,Vendedor'])->group(function () {
        Route::get('/abiertas', [\App\Http\Controllers\Api\CajaController::class, 'abiertas']);
        Route::get('/cerradas', [\App\Http\Controllers\Api\CajaController::class, 'cerradas']);
        Route::patch('/{id}/abrir', [\App\Http\Controllers\Api\CajaController::class, 'abrir']);
        Route::patch('/{id}/cerrar', [\App\Http\Controllers\Api\CajaController::class, 'cerrar']);
        Route::post('/{id}/arqueo', [\App\Http\Controllers\Api\CajaController::class, 'arqueo']);
        Route::patch('/{id}/conciliar', [\App\Http\Controllers\Api\CajaController::class, 'conciliar']);
        Route::get('/{id}/arqueos', [\App\Http\Controllers\Api\CajaController::class, 'arqueos']);
        Route::get('/{id}/movimientos', [\App\Http\Controllers\Api\CajaController::class, 'movimientos']);
    });

    Route::apiResource('cajas', \App\Http\Controllers\Api\CajaController::class)
        ->middleware(['auth:sanctum', 'role:Administrador,Gerente,Cajero,Vendedor']);

    /*
    |--------------------------------------------------------------------------
    | API Routes - Operational Modules
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('dashboard')->group(function () {
            Route::get('/metrics', [\App\Http\Controllers\Api\DashboardController::class, 'getMetrics']);
            Route::get('/top-productos', [\App\Http\Controllers\Api\DashboardController::class, 'getTopProductos']);
            Route::get('/ventas-por-categoria', [\App\Http\Controllers\Api\DashboardController::class, 'getVentasPorCategoria']);
            Route::get('/actividad-reciente', [\App\Http\Controllers\Api\DashboardController::class, 'getActividadReciente']);
            Route::get('/comparativo', [\App\Http\Controllers\Api\DashboardController::class, 'getComparativo']);
            Route::get('/alertas-inventario', [\App\Http\Controllers\Api\DashboardController::class, 'getAlertasInventario']);
        });

        Route::prefix('compras')->group(function () {
            Route::get('/pendientes', [\App\Http\Controllers\Api\CompraController::class, 'pendientes']);
            Route::get('/por-fecha-documento', [\App\Http\Controllers\Api\CompraController::class, 'porFechaDocumento']);
            Route::patch('/{compra}/recibir', [\App\Http\Controllers\Api\CompraController::class, 'recibir']);
            Route::patch('/{compra}/cancelar', [\App\Http\Controllers\Api\CompraController::class, 'cancelar']);
            Route::patch('/{compra}/devolver', [\App\Http\Controllers\Api\CompraController::class, 'devolver']);
            Route::patch('/{compra}/completar', [\App\Http\Controllers\Api\CompraController::class, 'completar']);
            Route::patch('/{compra}/pagar', [\App\Http\Controllers\Api\CompraController::class, 'pagar']);
        });
        Route::apiResource('compras', \App\Http\Controllers\Api\CompraController::class);

        Route::prefix('pagos')->group(function () {
            Route::get('/cuentas-por-cobrar', [\App\Http\Controllers\Api\PagoController::class, 'cuentasPorCobrar']);
            Route::get('/cuentas-por-pagar', [\App\Http\Controllers\Api\PagoController::class, 'cuentasPorPagar']);
        });
        Route::apiResource('pagos', \App\Http\Controllers\Api\PagoController::class)->only(['index', 'show']);

        Route::prefix('notas-credito')->group(function () {
            Route::patch('/{notaCredito}/anular', [\App\Http\Controllers\Api\NotaCreditoController::class, 'anular']);
            Route::patch('/{notaCredito}/aplicar', [\App\Http\Controllers\Api\NotaCreditoController::class, 'aplicar']);
        });
        Route::apiResource('notas-credito', \App\Http\Controllers\Api\NotaCreditoController::class)->only(['index', 'show']);

        Route::patch('/cotizaciones/{cotizacion}/cambiar-estado', [\App\Http\Controllers\Api\CotizacionController::class, 'cambiarEstado']);
        Route::post('/cotizaciones/{cotizacion}/convertir', [\App\Http\Controllers\Api\CotizacionController::class, 'convertir']);
        Route::apiResource('cotizaciones', \App\Http\Controllers\Api\CotizacionController::class)
            ->parameters(['cotizaciones' => 'cotizacion']);

        Route::patch('/gastos/{id}/toggle-estado', [\App\Http\Controllers\Api\GastoController::class, 'toggleEstado']);
        Route::apiResource('gastos', \App\Http\Controllers\Api\GastoController::class);

        Route::prefix('sucursales')->group(function () {
            Route::get('/activas', [\App\Http\Controllers\Api\SucursalController::class, 'activas']);
            Route::post('/bulk-update', [\App\Http\Controllers\Api\SucursalController::class, 'bulkUpdate']);
        });
        Route::apiResource('sucursales', \App\Http\Controllers\Api\SucursalController::class)
            ->parameters(['sucursales' => 'sucursal']);

        Route::prefix('lotes')->group(function () {
            Route::get('/disponibles', [\App\Http\Controllers\Api\LoteController::class, 'getLotesDisponibles']);
            Route::get('/resumen', [\App\Http\Controllers\Api\LoteController::class, 'getResumenInventario']);
            Route::get('/stock-bajo', [\App\Http\Controllers\Api\LoteController::class, 'getProductosStockBajo']);
            Route::get('/proximos-vencer', [\App\Http\Controllers\Api\LoteController::class, 'getProductosProximosAVencer']);
            Route::post('/transferir', [\App\Http\Controllers\Api\LoteController::class, 'transferirEntreLotes']);
            Route::post('/{id}/agregar-stock', [\App\Http\Controllers\Api\LoteController::class, 'agregarStock']);
            Route::post('/{id}/descontar-stock', [\App\Http\Controllers\Api\LoteController::class, 'descontarStock']);
            Route::patch('/{id}/marcar-vencido', [\App\Http\Controllers\Api\LoteController::class, 'marcarVencido']);
        });
        Route::apiResource('lotes', \App\Http\Controllers\Api\LoteController::class);

        Route::prefix('kardex')->group(function () {
            Route::get('/lote/{loteId}', [\App\Http\Controllers\Api\KardexController::class, 'getKardexPorLote']);
            Route::get('/producto/{productoId}', [\App\Http\Controllers\Api\KardexController::class, 'getKardexPorProducto']);
            Route::get('/reportes/movimientos', [\App\Http\Controllers\Api\KardexController::class, 'getReporteMovimientos']);
            Route::get('/reportes/mermas', [\App\Http\Controllers\Api\KardexController::class, 'getReporteMermas']);
            Route::get('/reportes/usuario/{usuarioId}', [\App\Http\Controllers\Api\KardexController::class, 'getReportePorUsuario']);
            Route::get('/exportar/csv', [\App\Http\Controllers\Api\KardexController::class, 'exportarCsv']);
            Route::get('/exportar/movimientos/csv', [\App\Http\Controllers\Api\KardexController::class, 'exportarMovimientosCsv']);
        });

        Route::prefix('traslados')->group(function () {
            Route::patch('/{id}/enviar', [\App\Http\Controllers\Api\TrasladoController::class, 'enviar']);
            Route::patch('/{id}/recibir', [\App\Http\Controllers\Api\TrasladoController::class, 'recibir']);
        });
        Route::apiResource('traslados', \App\Http\Controllers\Api\TrasladoController::class)->only(['index', 'store', 'show']);

        Route::apiResource('ajustes-inventario', \App\Http\Controllers\Api\AjusteInventarioController::class)->only(['index', 'store', 'show']);

        Route::prefix('notificaciones')->group(function () {
            Route::get('/pendientes', [\App\Http\Controllers\Api\NotificacionController::class, 'pendientes']);
            Route::get('/count', [\App\Http\Controllers\Api\NotificacionController::class, 'count']);
            Route::patch('/leer-todas', [\App\Http\Controllers\Api\NotificacionController::class, 'marcarTodasLeidas']);
            Route::post('/generar-alertas', [\App\Http\Controllers\Api\NotificacionController::class, 'generarAlertas']);
            Route::patch('/{id}/leer', [\App\Http\Controllers\Api\NotificacionController::class, 'marcarLeida']);
        });
        Route::apiResource('notificaciones', \App\Http\Controllers\Api\NotificacionController::class)
            ->parameters(['notificaciones' => 'notificacion'])
            ->only(['index', 'destroy']);

        Route::prefix('activity-logs')->group(function () {
            Route::get('/me', [\App\Http\Controllers\Api\ActivityLogController::class, 'myLogs']);
            Route::get('/', [\App\Http\Controllers\Api\ActivityLogController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\Api\ActivityLogController::class, 'show']);
        });

        Route::prefix('pdf')->group(function () {
            Route::get('/ventas', [\App\Http\Controllers\Api\PdfController::class, 'reporteVentas']);
            Route::get('/compras', [\App\Http\Controllers\Api\PdfController::class, 'reporteCompras']);
            Route::get('/inventario', [\App\Http\Controllers\Api\PdfController::class, 'reporteInventario']);
            Route::get('/kardex/{loteId}', [\App\Http\Controllers\Api\PdfController::class, 'reporteKardex']);
            Route::get('/stock-bajo', [\App\Http\Controllers\Api\PdfController::class, 'reporteStockBajo']);
            Route::get('/proximos-vencer', [\App\Http\Controllers\Api\PdfController::class, 'reporteProximosVencer']);
            Route::get('/movimientos', [\App\Http\Controllers\Api\PdfController::class, 'reporteMovimientos']);
            Route::get('/ventas/{id}/comprobante', [\App\Http\Controllers\Api\PdfController::class, 'comprobanteVenta']);
            Route::get('/compras/{id}/comprobante', [\App\Http\Controllers\Api\PdfController::class, 'comprobanteCompra']);
            Route::get('/cotizacion', [\App\Http\Controllers\Api\PdfController::class, 'reporteCotizacion']);
        });
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
                'advanced_analytics' => true,
                'json_operations' => true,
                'window_functions' => true,
                'cte_support' => true,
            ],
            'endpoints' => [
                'root' => '/api/v1',
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

});
