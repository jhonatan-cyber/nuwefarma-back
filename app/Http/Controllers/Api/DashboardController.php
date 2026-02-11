<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Models\Compra;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\MovimientoLote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Dashboard', description: 'Métricas y estadísticas del dashboard')]
class DashboardController extends Controller
{
    #[OA\Get(
        path: '/api/dashboard/metrics',
        summary: 'Obtener métricas principales del dashboard',
        security: [['bearerAuth' => []]],
        tags: ['Dashboard'],
        parameters: [
            new OA\Parameter(name: 'periodo', in: 'query', description: 'Periodo: hoy, semana, mes, año', required: false),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Métricas del dashboard'),
        ]
    )]
    public function getMetrics(Request $request): JsonResponse
    {
        $periodo = $request->get('periodo', 'mes');
        $cacheKey = "dashboard_metrics_{$periodo}";

        $metrics = Cache::remember($cacheKey, 300, function () use ($periodo) {
            return $this->calculateMetrics($periodo);
        });

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    private function calculateMetrics(string $periodo): array
    {
        $fechaInicio = $this->getFechaInicio($periodo);

        return [
            'ventas' => $this->getVentasMetrics($fechaInicio),
            'compras' => $this->getComprasMetrics($fechaInicio),
            'inventario' => $this->getInventarioMetrics(),
            'productos' => $this->getProductosMetrics(),
        ];
    }

    private function getFechaInicio(string $periodo): string
    {
        return match ($periodo) {
            'hoy' => now()->startOfDay(),
            'semana' => now()->startOfWeek(),
            'mes' => now()->startOfMonth(),
            'año' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };
    }

    private function getVentasMetrics($fechaInicio): array
    {
        $ventas = Venta::where('fecha_venta', '>=', $fechaInicio)
            ->where('estado', '!=', 'cancelada');

        $totalVentas = $ventas->sum('total');
        $cantidadVentas = $ventas->count();
        $promedioVenta = $cantidadVentas > 0 ? $totalVentas / $cantidadVentas : 0;

        $ventasPorDia = Venta::where('fecha_venta', '>=', $fechaInicio)
            ->where('estado', '!=', 'cancelada')
            ->selectRaw('DATE(fecha_venta) as dia, SUM(total) as total, COUNT(*) as cantidad')
            ->groupBy('dia')
            ->orderBy('dia', 'asc')
            ->get();

        return [
            'total' => $totalVentas,
            'cantidad' => $cantidadVentas,
            'promedio' => round($promedioVenta, 2),
            'por_dia' => $ventasPorDia->toArray(),
        ];
    }

    private function getComprasMetrics($fechaInicio): array
    {
        $compras = Compra::where('fecha_compra', '>=', $fechaInicio)
            ->where('estado', '!=', 'cancelada');

        $totalCompras = $compras->sum('total');
        $cantidadCompras = $compras->count();

        return [
            'total' => $totalCompras,
            'cantidad' => $cantidadCompras,
        ];
    }

    private function getInventarioMetrics(): array
    {
        $lotesActivos = Lote::whereIn('estado', ['disponible', 'parcial'])->count();
        $stockTotal = Lote::whereIn('estado', ['disponible', 'parcial'])->sum('stock');
        $valorInventario = Lote::whereIn('estado', ['disponible', 'parcial'])
            ->get()
            ->sum(fn($l) => $l->stock * $l->precio_costo);

        $lotesVencidos = Lote::where('estado', 'vencido')->count();
        $lotesProximosVencer = Lote::proximosAVencer(30)->count();
        $lotesStockBajo = Lote::stockBajo()->count();

        return [
            'total_lotes' => $lotesActivos,
            'stock_total' => $stockTotal,
            'valor_inventario' => round($valorInventario, 2),
            'vencidos' => $lotesVencidos,
            'proximos_vencer' => $lotesProximosVencer,
            'stock_bajo' => $lotesStockBajo,
        ];
    }

    private function getProductosMetrics(): array
    {
        $totalProductos = Producto::where('estado', 'activo')->count();
        $productosConStock = Producto::whereHas('lotes', function ($query) {
            $query->whereIn('estado', ['disponible', 'parcial'])->where('stock', '>', 0);
        })->count();

        return [
            'total' => $totalProductos,
            'con_stock' => $productosConStock,
            'sin_stock' => $totalProductos - $productosConStock,
        ];
    }

    #[OA\Get(
        path: '/api/dashboard/top-productos',
        summary: 'Obtener top productos',
        security: [['bearerAuth' => []]],
        tags: ['Dashboard'],
        parameters: [
            new OA\Parameter(name: 'tipo', in: 'query', description: 'tipo: mas_vendidos, menos_vendidos', required: false),
            new OA\Parameter(name: 'limite', in: 'query', description: 'Número de productos', required: false),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Top productos'),
        ]
    )]
    public function getTopProductos(Request $request): JsonResponse
    {
        $tipo = $request->get('tipo', 'mas_vendidos');
        $limite = $request->get('limite', 10);

        $productos = Venta::where('ventas.estado', '!=', 'cancelada')
            ->where('ventas.created_at', '>=', now()->subDays(30))
            ->join('venta_productos', 'ventas.id', '=', 'venta_productos.venta_id')
            ->join('productos', 'venta_productos.producto_id', '=', 'productos.id')
            ->selectRaw('productos.id, productos.nombre, productos.codigo_barras, SUM(venta_productos.cantidad) as total_vendido, SUM(venta_productos.subtotal) as total_ingresos')
            ->groupBy('productos.id', 'productos.nombre', 'productos.codigo_barras')
            ->orderBy('total_vendido', $tipo === 'mas_vendidos' ? 'desc' : 'asc')
            ->limit($limite)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $productos,
        ]);
    }

    #[OA\Get(
        path: '/api/dashboard/ventas-por-categoria',
        summary: 'Obtener ventas por categoría',
        security: [['bearerAuth' => []]],
        tags: ['Dashboard'],
        parameters: [
            new OA\Parameter(name: 'periodo', in: 'query', description: 'Periodo', required: false),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Ventas por categoría'),
        ]
    )]
    public function getVentasPorCategoria(Request $request): JsonResponse
    {
        $fechaInicio = now()->subDays(30);

        $ventasPorCategoria = Venta::where('ventas.created_at', '>=', $fechaInicio)
            ->where('ventas.estado', '!=', 'cancelada')
            ->join('venta_productos', 'ventas.id', '=', 'venta_productos.venta_id')
            ->join('productos', 'venta_productos.producto_id', '=', 'productos.id')
            ->leftJoin('categoria_producto', 'productos.id', '=', 'categoria_producto.producto_id')
            ->leftJoin('categorias', 'categoria_producto.categoria_id', '=', 'categorias.id')
            ->selectRaw('categorias.id, categorias.nombre as categoria, SUM(venta_productos.subtotal) as total, COUNT(DISTINCT venta_productos.id) as transacciones')
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderBy('total', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $ventasPorCategoria,
        ]);
    }

    #[OA\Get(
        path: '/api/dashboard/actividad-reciente',
        summary: 'Obtener actividad reciente',
        security: [['bearerAuth' => []]],
        tags: ['Dashboard'],
        responses: [
            new OA\Response(response: 200, description: 'Actividad reciente'),
        ]
    )]
    public function getActividadReciente(): JsonResponse
    {
        $ventasRecientes = Venta::with(['cliente', 'usuario'])
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($v) => [
                'tipo' => 'venta',
                'titulo' => "Venta #{$v->numero_venta}",
                'descripcion' => $v->cliente ? "Cliente: {$v->cliente->nombre}" : "Venta general",
                'monto' => $v->total,
                'usuario' => $v->usuario?->nombre,
                'fecha' => $v->created_at,
            ]);

        $comprasRecientes = Compra::with(['proveedor', 'usuario'])
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($c) => [
                'tipo' => 'compra',
                'titulo' => "Compra #{$c->numero_compra}",
                'descripcion' => $c->proveedor ? "Proveedor: {$c->proveedor->nombre}" : "Compra general",
                'monto' => $c->total,
                'usuario' => $c->usuario?->nombre,
                'fecha' => $c->created_at,
            ]);

        $actividades = $ventasRecientes->merge($comprasRecientes)
            ->sortByDesc('fecha')
            ->take(15)
            ->values();

        return response()->json([
            'success' => true,
            'data' => $actividades,
        ]);
    }

    #[OA\Get(
        path: '/api/dashboard/comparativo',
        summary: 'Obtener comparativo de ventas',
        security: [['bearerAuth' => []]],
        tags: ['Dashboard'],
        parameters: [
            new OA\Parameter(name: 'mes_actual', in: 'query', description: 'Mes actual', required: false),
            new OA\Parameter(name: 'mes_anterior', in: 'query', description: 'Mes anterior', required: false),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Comparativo de ventas'),
        ]
    )]
    public function getComparativo(Request $request): JsonResponse
    {
        $mesActual = now()->startOfMonth();
        $mesAnterior = now()->subMonth()->startOfMonth();

        $ventasMesActual = Venta::where('fecha_venta', '>=', $mesActual)
            ->where('estado', '!=', 'cancelada')
            ->sum('total');

        $ventasMesAnterior = Venta::where('fecha_venta', '>=', $mesAnterior)
            ->where('fecha_venta', '<', $mesActual)
            ->where('estado', '!=', 'cancelada')
            ->sum('total');

        $comparacion = $ventasMesAnterior > 0
            ? (($ventasMesActual - $ventasMesAnterior) / $ventasMesAnterior) * 100
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'mes_actual' => $ventasMesActual,
                'mes_anterior' => $ventasMesAnterior,
                'comparacion_porcentaje' => round($comparacion, 2),
                'tipo' => $comparacion >= 0 ? 'subio' : 'bajo',
            ],
        ]);
    }
}
