<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Venta;
use App\Models\MovimientoLote;
use App\Services\FinancialCalculatorService;
use Illuminate\Http\Request;
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
    public function getMetrics(Request $request)
    {
        $periodo = $request->get('periodo', 'mes');
        $cacheKey = "dashboard_metrics_{$periodo}";

        $metrics = Cache::remember($cacheKey, 300, function () use ($periodo) {
            return $this->calculateMetrics($periodo);
        });

        return $this->success($metrics);
    }

    private function calculateMetrics(string $periodo): array
    {
        $fechaInicio = $this->getFechaInicio($periodo);
        $fechaInicioAnterior = $this->getFechaInicioAnterior($periodo);

        return [
            'ventas' => $this->getVentasMetrics($fechaInicio, $fechaInicioAnterior),
            'compras' => $this->getComprasMetrics($fechaInicio),
            'inventario' => $this->getInventarioMetrics(),
            'productos' => $this->getProductosMetrics($fechaInicio, $fechaInicioAnterior),
            'clientes' => $this->getClientesMetrics($fechaInicio, $fechaInicioAnterior),
            'proveedores' => $this->getProveedoresMetrics($fechaInicio, $fechaInicioAnterior),
            'finanzas' => $this->getFinanzasMetrics(now()->startOfMonth()),
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

    private function getFechaInicioAnterior(string $periodo): string
    {
        return match ($periodo) {
            'hoy' => now()->subDay()->startOfDay(),
            'semana' => now()->subWeek()->startOfWeek(),
            'mes' => now()->subMonthNoOverflow()->startOfMonth(),
            'año' => now()->subYear()->startOfYear(),
            default => now()->subMonthNoOverflow()->startOfMonth(),
        };
    }

    private function calcularVariacion(float $actual, float $anterior): float
    {
        if ($anterior <= 0) {
            return 0.0;
        }

        return round((($actual - $anterior) / $anterior) * 100, 2);
    }

    private function getVentasMetrics($fechaInicio, $fechaInicioAnterior): array
    {
        $ventas = Venta::where('fecha_venta', '>=', $fechaInicio)
            ->where('estado', '!=', 'cancelada');

        $totalVentas = $ventas->sum('total');
        $cantidadVentas = $ventas->count();
        $promedioVenta = $cantidadVentas > 0 ? $totalVentas / $cantidadVentas : 0;

        $totalVentasAnterior = Venta::where('fecha_venta', '>=', $fechaInicioAnterior)
            ->where('fecha_venta', '<', $fechaInicio)
            ->where('estado', '!=', 'cancelada')
            ->sum('total');

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
            'crecimiento' => $this->calcularVariacion((float) $totalVentas, (float) $totalVentasAnterior),
            'por_dia' => $ventasPorDia->toArray(),
        ];
    }

    private function getComprasMetrics($fechaInicio): array
    {
        $compras = Compra::where('fecha_compra', '>=', $fechaInicio)
            ->where('estado', '!=', 'cancelada');

        $totalCompras = $compras->sum('total');
        $cantidadCompras = $compras->count();

        $conteoPorEstado = Compra::where('fecha_compra', '>=', $fechaInicio)
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return [
            'total' => $totalCompras,
            'cantidad' => $cantidadCompras,
            'por_estado' => [
                'pendiente' => (int) ($conteoPorEstado['pendiente'] ?? 0),
                'recibida' => (int) ($conteoPorEstado['recibida'] ?? 0),
                'cancelada' => (int) ($conteoPorEstado['cancelada'] ?? 0),
                'devuelta' => (int) ($conteoPorEstado['devuelta'] ?? 0),
            ],
        ];
    }

    /**
     * Métricas financieras consolidadas del mes en curso: lo cobrado, el
     * saldo por cobrar y la utilidad bruta real según el costo de inventario.
     */
    private function getFinanzasMetrics($fechaInicio): array
    {
        $calculador = app(FinancialCalculatorService::class);

        $ventasMes = Venta::where('fecha_venta', '>=', $fechaInicio)
            ->where('estado', '!=', 'cancelada')
            ->get();

        $ganancias = [
            'costo_ventas' => $ventasMes->sum(fn (Venta $venta) => $calculador->costoVenta($venta)),
            'ingresos' => $ventasMes->sum(fn (Venta $venta) => $calculador->totalCobrado($venta)),
            'saldo_por_cobrar' => $ventasMes->sum(fn (Venta $venta) => $calculador->saldoPorCobrar($venta)),
            'utilidad_bruta' => $ventasMes->sum(fn (Venta $venta) => $calculador->utilidadVenta($venta)),
        ];

        $totalFacturado = $ganancias['ingresos'] + $ganancias['saldo_por_cobrar'];

        $ganancias['margen_utilidad'] = $totalFacturado > 0
            ? round(($ganancias['utilidad_bruta'] / $totalFacturado) * 100, 2)
            : 0.0;

        $ganancias['costo_ventas'] = round($ganancias['costo_ventas'], 2);
        $ganancias['ingresos'] = round($ganancias['ingresos'], 2);
        $ganancias['saldo_por_cobrar'] = round($ganancias['saldo_por_cobrar'], 2);
        $ganancias['utilidad_bruta'] = round($ganancias['utilidad_bruta'], 2);

        return [
            'mensual' => [
                'ingresos' => $ganancias['ingresos'],
                'costo_ventas' => $ganancias['costo_ventas'],
                'utilidad_bruta' => $ganancias['utilidad_bruta'],
                'margen_utilidad' => $ganancias['margen_utilidad'],
                'saldo_por_cobrar' => $ganancias['saldo_por_cobrar'],
            ],
        ];
    }

    private function getInventarioMetrics(): array
    {
        $lotesActivos = Lote::whereIn('estado', ['disponible', 'parcial'])->count();
        $stockTotal = Lote::whereIn('estado', ['disponible', 'parcial'])->sum('stock');
        $valorInventario = Lote::whereIn('estado', ['disponible', 'parcial'])
            ->get()
            ->sum(fn ($l) => $l->stock * $l->precio_costo);

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

    private function getProductosMetrics($fechaInicio, $fechaInicioAnterior): array
    {
        $totalProductos = Producto::where('estado', 'activo')->count();
        $productosConStock = Producto::whereHas('lotes', function ($query) {
            $query->whereIn('estado', ['disponible', 'parcial'])->where('stock', '>', 0);
        })->count();

        $nuevosProductos = Producto::where('created_at', '>=', $fechaInicio)->count();
        $nuevosProductosAnterior = Producto::where('created_at', '>=', $fechaInicioAnterior)
            ->where('created_at', '<', $fechaInicio)
            ->count();

        return [
            'total' => $totalProductos,
            'con_stock' => $productosConStock,
            'sin_stock' => $totalProductos - $productosConStock,
            'crecimiento' => $this->calcularVariacion($nuevosProductos, $nuevosProductosAnterior),
        ];
    }

    private function getClientesMetrics($fechaInicio, $fechaInicioAnterior): array
    {
        $total = Cliente::count();
        $activos = Cliente::where('estado', 'activo')->count();

        $nuevos = Cliente::where('created_at', '>=', $fechaInicio)->count();
        $nuevosAnterior = Cliente::where('created_at', '>=', $fechaInicioAnterior)
            ->where('created_at', '<', $fechaInicio)
            ->count();

        return [
            'total' => $total,
            'activos' => $activos,
            'inactivos' => $total - $activos,
            'nuevos' => $nuevos,
            'crecimiento' => $this->calcularVariacion($nuevos, $nuevosAnterior),
        ];
    }

    private function getProveedoresMetrics($fechaInicio, $fechaInicioAnterior): array
    {
        $total = Proveedor::count();
        $activos = Proveedor::where('estado', 'activo')->count();

        $nuevos = Proveedor::where('created_at', '>=', $fechaInicio)->count();
        $nuevosAnterior = Proveedor::where('created_at', '>=', $fechaInicioAnterior)
            ->where('created_at', '<', $fechaInicio)
            ->count();

        return [
            'total' => $total,
            'activos' => $activos,
            'inactivos' => $total - $activos,
            'nuevos' => $nuevos,
            'crecimiento' => $this->calcularVariacion($nuevos, $nuevosAnterior),
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
    public function getTopProductos(Request $request)
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

        return $this->success($productos);
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
    public function getVentasPorCategoria(Request $request)
    {
        $fechaInicio = now()->subDays(30);

        $ventasPorCategoria = Venta::where('ventas.created_at', '>=', $fechaInicio)
            ->where('ventas.estado', '!=', 'cancelada')
            ->join('venta_productos', 'ventas.id', '=', 'venta_productos.venta_id')
            ->join('productos', 'venta_productos.producto_id', '=', 'productos.id')
            ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->selectRaw('categorias.id, categorias.nombre as categoria, SUM(venta_productos.subtotal) as total, COUNT(DISTINCT venta_productos.id) as transacciones')
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderBy('total', 'desc')
            ->get();

        return $this->success($ventasPorCategoria);
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
    public function getActividadReciente()
    {
        $ventasRecientes = Venta::with(['cliente', 'usuario'])
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($v) => [
                'tipo' => 'venta',
                'titulo' => "Venta #{$v->numero_venta}",
                'descripcion' => $v->cliente ? "Cliente: {$v->cliente->nombre}" : 'Venta general',
                'monto' => $v->total,
                'usuario' => $v->usuario?->nombre,
                'fecha' => $v->created_at,
            ]);

        $comprasRecientes = Compra::with(['proveedor', 'usuario'])
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($c) => [
                'tipo' => 'compra',
                'titulo' => "Compra #{$c->numero_compra}",
                'descripcion' => $c->proveedor ? "Proveedor: {$c->proveedor->nombre}" : 'Compra general',
                'monto' => $c->total,
                'usuario' => $c->usuario?->nombre,
                'fecha' => $c->created_at,
            ]);

        $actividades = $ventasRecientes->merge($comprasRecientes)
            ->sortByDesc('fecha')
            ->take(15)
            ->values();

        return $this->success($actividades);
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
    public function getComparativo(Request $request)
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

        return $this->success([
            'mes_actual' => $ventasMesActual,
            'mes_anterior' => $ventasMesAnterior,
            'comparacion_porcentaje' => round($comparacion, 2),
            'tipo' => $comparacion >= 0 ? 'subio' : 'bajo',
        ]);
    }

    #[OA\Get(
        path: '/api/dashboard/alertas-inventario',
        summary: 'Obtener listas de productos con alertas de inventario',
        security: [['bearerAuth' => []]],
        tags: ['Dashboard'],
        parameters: [
            new OA\Parameter(name: 'dias', in: 'query', description: 'Días para considerar próximo a vencer', required: false),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Alertas de inventario'),
        ]
    )]
    public function getAlertasInventario(Request $request)
    {
        $dias = max(1, (int) $request->get('dias', 30));

        $bajoStock = Producto::whereColumn('stock_actual', '<=', 'stock_minimo')
            ->orderBy('stock_actual', 'asc')
            ->limit(50)
            ->get()
            ->map(fn (Producto $p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'codigo_barras' => $p->codigo_barras,
                'lote' => $p->lote,
                'stock' => $p->stock_actual,
                'stock_minimo' => $p->stock_minimo,
            ])
            ->values();

        $proximosVencer = Producto::where('fecha_vencimiento', '>', now())
            ->where('fecha_vencimiento', '<=', now()->addDays($dias))
            ->orderBy('fecha_vencimiento', 'asc')
            ->limit(50)
            ->get()
            ->map(fn (Producto $p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'codigo_barras' => $p->codigo_barras,
                'lote' => $p->lote,
                'fecha_vencimiento' => $p->fecha_vencimiento?->format('Y-m-d'),
                'stock' => $p->stock_actual,
                'stock_minimo' => $p->stock_minimo,
            ])
            ->values();

        $vencidos = Producto::where('fecha_vencimiento', '<=', now())
            ->orderBy('fecha_vencimiento', 'asc')
            ->limit(50)
            ->get()
            ->map(fn (Producto $p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'codigo_barras' => $p->codigo_barras,
                'lote' => $p->lote,
                'fecha_vencimiento' => $p->fecha_vencimiento?->format('Y-m-d'),
                'stock' => $p->stock_actual,
                'stock_minimo' => $p->stock_minimo,
            ])
            ->values();

        return $this->success([
            'bajo_stock' => $bajoStock,
            'proximos_vencer' => $proximosVencer,
            'vencidos' => $vencidos,
        ]);
    }
}
