<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductoStatsController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $base = Producto::query();

        if ($estado = $request->query('estado')) {
            $base->where('estado', $estado);
        }
        if ($categoriaId = $request->query('categoria_id')) {
            $base->where('categoria_id', $categoriaId);
        }
        if ($proveedorId = $request->query('proveedor_id')) {
            $base->where('proveedor_id', $proveedorId);
        }

        $total = (clone $base)->count();
        $activos = (clone $base)->where('estado', 'activo')->count();
        $inactivos = (clone $base)->where('estado', 'inactivo')->count();
        $sinStock = (clone $base)->where('stock_actual', '<=', 0)->count();
        $stockBajo = (clone $base)->whereColumn('stock_actual', '<=', 'stock_minimo')->count();

        $hoy = now();
        $vencer30 = (clone $base)
            ->whereNotNull('fecha_vencimiento')
            ->whereBetween('fecha_vencimiento', [$hoy, (clone $hoy)->copy()->addDays(30)])
            ->count();
        $vencer60 = (clone $base)
            ->whereNotNull('fecha_vencimiento')
            ->whereBetween('fecha_vencimiento', [$hoy, (clone $hoy)->copy()->addDays(60)])
            ->count();
        $vencer90 = (clone $base)
            ->whereNotNull('fecha_vencimiento')
            ->whereBetween('fecha_vencimiento', [$hoy, (clone $hoy)->copy()->addDays(90)])
            ->count();

        $valorCompra = (float) ((clone $base)
            ->select(DB::raw('COALESCE(SUM(precio_compra * stock_actual), 0) as s'))
            ->value('s'));
        $valorVenta = (float) ((clone $base)
            ->select(DB::raw('COALESCE(SUM(precio_venta * stock_actual), 0) as s'))
            ->value('s'));
        $margenTeorico = $valorVenta - $valorCompra;
        $margenPonderadoPct = $valorVenta > 0 ? round(($margenTeorico / $valorVenta) * 100, 2) : 0.0;

        $porCategoria = (clone $base)
            ->leftJoin('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->select([
                DB::raw('productos.categoria_id as id'),
                DB::raw("COALESCE(categorias.nombre, 'Sin categoría') as nombre"),
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('COALESCE(SUM(precio_compra * stock_actual), 0) as valor_compra'),
                DB::raw('COALESCE(SUM(precio_venta * stock_actual), 0) as valor_venta'),
            ])
            ->groupBy('productos.categoria_id', 'categorias.nombre')
            ->orderByDesc('cantidad')
            ->limit(10)
            ->get();

        $porProveedor = (clone $base)
            ->leftJoin('proveedores', 'productos.proveedor_id', '=', 'proveedores.id')
            ->select([
                DB::raw('productos.proveedor_id as id'),
                DB::raw("COALESCE(proveedores.nombre, 'Sin proveedor') as nombre"),
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('COALESCE(SUM(precio_compra * stock_actual), 0) as valor_compra'),
                DB::raw('COALESCE(SUM(precio_venta * stock_actual), 0) as valor_venta'),
            ])
            ->groupBy('productos.proveedor_id', 'proveedores.nombre')
            ->orderByDesc('cantidad')
            ->limit(10)
            ->get();

        $sinProveedor = (clone $base)->whereNull('proveedor_id')->count();
        $sinCategoria = (clone $base)->whereNull('categoria_id')->count();

        $duplicados = [
            'codigo_barras' => Producto::query()
                ->whereNotNull('codigo_barras')
                ->select('codigo_barras', DB::raw('COUNT(*) as c'))
                ->groupBy('codigo_barras')
                ->having('c', '>', 1)
                ->count(),
            'sku' => Producto::query()
                ->whereNotNull('sku')
                ->select('sku', DB::raw('COUNT(*) as c'))
                ->groupBy('sku')
                ->having('c', '>', 1)
                ->count(),
            'codigo_interno' => Producto::query()
                ->whereNotNull('codigo_interno')
                ->select('codigo_interno', DB::raw('COUNT(*) as c'))
                ->groupBy('codigo_interno')
                ->having('c', '>', 1)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'resumen' => [
                    'total' => $total,
                    'activos' => $activos,
                    'inactivos' => $inactivos,
                    'sin_stock' => $sinStock,
                    'stock_bajo' => $stockBajo,
                ],
                'vencimientos' => [
                    'en_30_dias' => $vencer30,
                    'en_60_dias' => $vencer60,
                    'en_90_dias' => $vencer90,
                ],
                'valores' => [
                    'inventario_compra' => round($valorCompra, 2),
                    'inventario_venta' => round($valorVenta, 2),
                    'margen_teorico' => round($margenTeorico, 2),
                    'margen_ponderado_pct' => $margenPonderadoPct,
                ],
                'por_categoria' => $porCategoria,
                'por_proveedor' => $porProveedor,
                'calidad_datos' => [
                    'sin_proveedor' => $sinProveedor,
                    'sin_categoria' => $sinCategoria,
                    'duplicados' => $duplicados,
                ],
            ],
        ]);
    }
}
