<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\Lote;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    public function generarReporteVentas(array $filtros = []): \Barryvdh\DomPDF\PDF
    {
        $query = Venta::with(['cliente', 'usuario', 'sucursal', 'productos.producto']);

        if (! empty($filtros['fecha_inicio'])) {
            $query->where('fecha_venta', '>=', $filtros['fecha_inicio']);
        }
        if (! empty($filtros['fecha_fin'])) {
            $query->where('fecha_venta', '<=', $filtros['fecha_fin']);
        }
        if (! empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        $ventas = $query->orderBy('fecha_venta', 'desc')->get();

        $totalVentas = $ventas->where('estado', '!=', 'cancelada')->sum('total');
        $totalTransacciones = $ventas->where('estado', '!=', 'cancelada')->count();

        $data = [
            'titulo' => 'Reporte de Ventas',
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'filtros' => $filtros,
            'ventas' => $ventas,
            'resumen' => [
                'total_ventas' => $totalVentas,
                'total_transacciones' => $totalTransacciones,
                'promedio' => $totalTransacciones > 0 ? $totalVentas / $totalTransacciones : 0,
            ],
        ];

        $pdf = Pdf::loadView('pdf.ventas', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf;
    }

    public function generarReporteCompras(array $filtros = []): \Barryvdh\DomPDF\PDF
    {
        $query = Compra::with(['proveedor', 'usuario', 'sucursal', 'productos.producto']);

        if (! empty($filtros['fecha_inicio'])) {
            $query->where('fecha_compra', '>=', $filtros['fecha_inicio']);
        }
        if (! empty($filtros['fecha_fin'])) {
            $query->where('fecha_compra', '<=', $filtros['fecha_fin']);
        }
        if (! empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        $compras = $query->orderBy('fecha_compra', 'desc')->get();

        $totalCompras = $compras->where('estado', '!=', 'cancelada')->sum('total');
        $totalOrdenes = $compras->where('estado', '!=', 'cancelada')->count();

        $data = [
            'titulo' => 'Reporte de Compras',
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'filtros' => $filtros,
            'compras' => $compras,
            'resumen' => [
                'total_compras' => $totalCompras,
                'total_ordenes' => $totalOrdenes,
                'promedio' => $totalOrdenes > 0 ? $totalCompras / $totalOrdenes : 0,
            ],
        ];

        $pdf = Pdf::loadView('pdf.compras', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf;
    }

    public function generarReporteInventario(array $filtros = []): \Barryvdh\DomPDF\PDF
    {
        $query = Lote::with(['producto', 'producto.categorias']);

        if (! empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }
        if (! empty($filtros['producto_id'])) {
            $query->where('producto_id', $filtros['producto_id']);
        }

        $lotes = $query->orderBy('fecha_vencimiento', 'asc')->get();

        $stockTotal = $lotes->sum('stock');
        $valorTotal = $lotes->sum(fn ($l) => $l->stock * $l->precio_costo);
        $lotesVencidos = $lotes->where('estado', 'vencido')->count();
        $lotesProximosVencer = $lotes->filter(function ($lote) {
            return $lote->dias_para_vencer !== null && $lote->dias_para_vencer <= 30;
        })->count();

        $data = [
            'titulo' => 'Reporte de Inventario',
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'filtros' => $filtros,
            'lotes' => $lotes,
            'resumen' => [
                'stock_total' => $stockTotal,
                'valor_total' => $valorTotal,
                'lotes_vencidos' => $lotesVencidos,
                'lotes_proximos_vencer' => $lotesProximosVencer,
                'total_lotes' => $lotes->count(),
            ],
        ];

        $pdf = Pdf::loadView('pdf.inventario', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf;
    }

    public function generarReporteKardex(string $loteId, ?string $fechaInicio = null, ?string $fechaFin = null): \Barryvdh\DomPDF\PDF
    {
        $lote = Lote::with(['producto'])->findOrFail($loteId);

        $query = \App\Models\MovimientoLote::with(['usuario', 'sucursal'])
            ->where('lote_id', $loteId);

        if ($fechaInicio && $fechaFin) {
            $query->entreFechas($fechaInicio, $fechaFin);
        }

        $movimientos = $query->orderBy('created_at', 'desc')->get();

        $entradas = $movimientos->filter(fn ($m) => $m->esEntrada())->sum('cantidad');
        $salidas = $movimientos->filter(fn ($m) => $m->esSalida())->sum('cantidad');

        $data = [
            'titulo' => 'Reporte de Kardex',
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'lote' => $lote,
            'producto' => $lote->producto,
            'movimientos' => $movimientos,
            'resumen' => [
                'entradas' => $entradas,
                'salidas' => $salidas,
                'stock_actual' => $lote->stock,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ],
        ];

        $pdf = Pdf::loadView('pdf.kardex', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf;
    }

    public function generarReporteStockBajo(): \Barryvdh\DomPDF\PDF
    {
        $lotesStockBajo = Lote::stockBajo()->with(['producto'])->get();

        $agrupado = $lotesStockBajo->groupBy('producto_id')->map(function ($items, $productoId) {
            $producto = $items->first()->producto;

            return [
                'producto' => $producto,
                'lotes' => $items,
                'stock_total' => $items->sum('stock'),
                'stock_minimo' => $items->min('stock_minimo'),
            ];
        })->values();

        $data = [
            'titulo' => 'Reporte de Stock Bajo',
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'productos' => $agrupado,
            'total_productos' => $agrupado->count(),
        ];

        $pdf = Pdf::loadView('pdf.stock-bajo', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf;
    }

    public function generarReporteProximosVencer(int $dias = 60): \Barryvdh\DomPDF\PDF
    {
        $lotes = Lote::proximosAVencer($dias)
            ->with(['producto'])
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();

        $data = [
            'titulo' => 'Reporte de Productos Próximos a Vencer',
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'dias_alerta' => $dias,
            'lotes' => $lotes,
            'total_unidades' => $lotes->sum('stock'),
            'total_lotes' => $lotes->count(),
        ];

        $pdf = Pdf::loadView('pdf.proximos-vencer', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf;
    }

    public function generarReporteMovimientos(array $filtros = []): \Barryvdh\DomPDF\PDF
    {
        $query = \App\Models\MovimientoLote::with(['lote.producto', 'usuario', 'sucursal']);

        if (! empty($filtros['fecha_inicio'])) {
            $query->where('created_at', '>=', $filtros['fecha_inicio']);
        }
        if (! empty($filtros['fecha_fin'])) {
            $query->where('created_at', '<=', $filtros['fecha_fin']);
        }
        if (! empty($filtros['tipo_movimiento'])) {
            $query->where('tipo_movimiento', $filtros['tipo_movimiento']);
        }

        $movimientos = $query->orderBy('created_at', 'desc')->get();

        $resumenPorTipo = $movimientos->groupBy('tipo_movimiento')->map(function ($items, $tipo) {
            return [
                'tipo' => $tipo,
                'cantidad' => $items->sum('cantidad'),
                'costo_total' => $items->sum('costo_total'),
                'movimientos' => $items->count(),
            ];
        })->values();

        $data = [
            'titulo' => 'Reporte de Movimientos de Inventario',
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'filtros' => $filtros,
            'movimientos' => $movimientos,
            'resumen_por_tipo' => $resumenPorTipo,
            'total_movimientos' => $movimientos->count(),
        ];

        $pdf = Pdf::loadView('pdf.movimientos', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf;
    }

    public function generarComprobanteVenta(string $ventaId): \Barryvdh\DomPDF\PDF
    {
        $venta = Venta::with(['cliente', 'usuario', 'sucursal', 'productos.producto'])
            ->findOrFail($ventaId);

        $data = [
            'titulo' => 'Comprobante de Venta',
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'venta' => $venta,
            'tipo_comprobante' => ' ticket de Venta',
        ];

        $pdf = Pdf::loadView('pdf.comprobante-venta', $data);
        $pdf->setPaper([0, 0, 226.77, 600], 'portrait');

        return $pdf;
    }

    public function generarComprobanteCompra(string $compraId): \Barryvdh\DomPDF\PDF
    {
        $compra = Compra::with(['proveedor', 'usuario', 'sucursal', 'productos.producto'])
            ->findOrFail($compraId);

        $data = [
            'titulo' => 'Comprobante de Compra',
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'compra' => $compra,
            'tipo_comprobante' => ' comprobante de Compra',
        ];

        $pdf = Pdf::loadView('pdf.comprobante-compra', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf;
    }
}
