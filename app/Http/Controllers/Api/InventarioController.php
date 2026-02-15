<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Producto\ProductoCollection;
use App\Models\AjusteInventario;
use App\Models\MovimientoLote;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventarioController extends Controller
{
    /**
     * Get inventory summary.
     */
    public function resumen(): JsonResponse
    {
        $stats = [
            'total_productos' => Producto::count(),
            'productos_activos' => Producto::where('estado', 'activo')->count(),
            'productos_inactivos' => Producto::where('estado', 'inactivo')->count(),
            'stock_bajo' => Producto::whereColumn('stock_actual', '<=', 'stock_minimo')->count(),
            'proximos_vencer' => Producto::whereHas('lotes', function ($query) {
                $query->where('fecha_vencimiento', '<=', now()->addDays(30));
            })->count(),
        ];

        return $this->success($stats);
    }

    /**
     * Get inventory movements.
     */
    public function movimientos(Request $request): ProductoCollection
    {
        $filters = $request->only([
            'producto_id', 'tipo', 'fecha_inicio', 'fecha_fin', 'per_page',
        ]);

        $query = MovimientoLote::query()
            ->with(['producto', 'lote', 'usuario']);

        if (!empty($filters['producto_id'])) {
            $query->where('producto_id', $filters['producto_id']);
        }

        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (!empty($filters['fecha_inicio'])) {
            $query->whereDate('created_at', '>=', $filters['fecha_inicio']);
        }

        if (!empty($filters['fecha_fin'])) {
            $query->whereDate('created_at', '<=', $filters['fecha_fin']);
        }

        $movimientos = $query->latest()->paginate($filters['per_page'] ?? 15);

        return new ProductoCollection($movimientos);
    }

    /**
     * Get stock alerts.
     */
    public function alertas(): JsonResponse
    {
        $productosBajoStock = Producto::whereColumn('stock_actual', '<=', 'stock_minimo')
            ->with(['categoria'])
            ->limit(50)
            ->get();

        return $this->success([
            'bajo_stock' => $productosBajoStock,
            'total_alertas' => $productosBajoStock->count(),
        ]);
    }
}
