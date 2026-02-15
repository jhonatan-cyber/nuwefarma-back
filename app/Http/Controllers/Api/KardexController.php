<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MovimientoLote;
use App\Services\InventarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KardexController extends Controller
{
    private InventarioService $inventarioService;

    public function __construct(InventarioService $inventarioService)
    {
        $this->inventarioService = $inventarioService;
    }

    /**
     * Obtener Kardex de un lote específico
     */
    public function getKardexPorLote(Request $request, string $loteId): JsonResponse
    {
        try {
            $movimientos = $this->inventarioService->getKardex(
                $loteId,
                $request->fecha_inicio,
                $request->fecha_fin
            );

            // Calcular totales
            $totalEntradas = collect($movimientos)->filter(fn ($m) => in_array($m['tipo_movimiento'], [
                'ENTRADA_COMPRA', 'ENTRADA_DEVOLUCION', 'ENTRADA_TRASLADO_IN', 'AJUSTE_POSITIVO',
            ])
            )->sum('cantidad');

            $totalSalidas = collect($movimientos)->filter(fn ($m) => in_array($m['tipo_movimiento'], [
                'SALIDA_VENTA', 'SALIDA_DEVOLUCION_PROV', 'SALIDA_TRASLADO_OUT',
                'AJUSTE_NEGATIVO', 'SALIDA_MERMA', 'SALIDA_RETIRADO',
            ])
            )->sum('cantidad');

            $valorTotal = collect($movimientos)->sum('costo_total');

            return response()->json([
                'success' => true,
                'data' => [
                    'movimientos' => $movimientos,
                    'resumen' => [
                        'total_entradas' => $totalEntradas,
                        'total_salidas' => $totalSalidas,
                        'saldo_neto' => $totalEntradas - $totalSalidas,
                        'valor_total_movimientos' => $valorTotal,
                    ],
                ],
                'message' => 'Kardex obtenido correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener kardex por lote: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener kardex: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener Kardex de un producto (todos los lotes)
     */
    public function getKardexPorProducto(Request $request, string $productoId): JsonResponse
    {
        try {
            $movimientos = $this->inventarioService->getKardexPorProducto(
                $productoId,
                $request->fecha_inicio,
                $request->fecha_fin
            );

            // Calcular totales por tipo
            $resumenPorTipo = [];
            foreach ($movimientos as $m) {
                $tipo = $m['tipo_movimiento'];
                if (! isset($resumenPorTipo[$tipo])) {
                    $resumenPorTipo[$tipo] = [
                        'tipo' => $tipo,
                        'cantidad' => 0,
                        'costo_total' => 0,
                    ];
                }
                $resumenPorTipo[$tipo]['cantidad'] += $m['cantidad'];
                $resumenPorTipo[$tipo]['costo_total'] += $m['costo_total'] ?? 0;
            }

            $totalEntradas = collect($movimientos)->filter(fn ($m) => in_array($m['tipo_movimiento'], [
                'ENTRADA_COMPRA', 'ENTRADA_DEVOLUCION', 'ENTRADA_TRASLADO_IN', 'AJUSTE_POSITIVO',
            ])
            )->sum('cantidad');

            $totalSalidas = collect($movimientos)->filter(fn ($m) => in_array($m['tipo_movimiento'], [
                'SALIDA_VENTA', 'SALIDA_DEVOLUCION_PROV', 'SALIDA_TRASLADO_OUT',
                'AJUSTE_NEGATIVO', 'SALIDA_MERMA', 'SALIDA_RETIRADO',
            ])
            )->sum('cantidad');

            return response()->json([
                'success' => true,
                'data' => [
                    'producto_id' => $productoId,
                    'movimientos' => $movimientos,
                    'resumen' => [
                        'total_movimientos' => count($movimientos),
                        'total_entradas' => $totalEntradas,
                        'total_salidas' => $totalSalidas,
                        'saldo_neto' => $totalEntradas - $totalSalidas,
                        'por_tipo' => array_values($resumenPorTipo),
                    ],
                ],
                'message' => 'Kardex por producto obtenido correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener kardex por producto: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener kardex: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener reporte de movimientos por período
     */
    public function getReporteMovimientos(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
                'tipo_movimiento' => 'nullable|in:ENTRADA_COMPRA,ENTRADA_DEVOLUCION,ENTRADA_TRASLADO_IN,SALIDA_VENTA,SALIDA_DEVOLUCION_PROV,SALIDA_TRASLADO_OUT,AJUSTE_POSITIVO,AJUSTE_NEGATIVO,SALIDA_MERMA,SALIDA_RETIRADO',
                'sucursal_id' => 'nullable|uuid',
            ]);

            $query = MovimientoLote::with(['lote.producto', 'usuario', 'sucursal'])
                ->entreFechas($request->fecha_inicio, $request->fecha_fin);

            if ($request->tipo_movimiento) {
                $query->where('tipo_movimiento', $request->tipo_movimiento);
            }

            if ($request->sucursal_id) {
                $query->where('sucursal_id', $request->sucursal_id);
            }

            $movimientos = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 50);

            // Resumen por tipo
            $resumenPorTipo = $query->clone()
                ->selectRaw('tipo_movimiento, SUM(cantidad) as total_cantidad, SUM(costo_total) as total_costo')
                ->groupBy('tipo_movimiento')
                ->get()
                ->map(fn ($item) => [
                    'tipo' => $item->tipo_movimiento,
                    'cantidad' => (int) $item->total_cantidad,
                    'costo_total' => (float) $item->total_costo,
                ])
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'movimientos' => $movimientos,
                    'resumen_por_tipo' => $resumenPorTipo,
                    'filtros' => [
                        'fecha_inicio' => $request->fecha_inicio,
                        'fecha_fin' => $request->fecha_fin,
                        'tipo' => $request->tipo_movimiento,
                        'sucursal_id' => $request->sucursal_id,
                    ],
                ],
                'message' => 'Reporte de movimientos obtenido correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener reporte de movimientos: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reporte: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener reporte de mermas
     */
    public function getReporteMermas(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date',
            ]);

            $query = MovimientoLote::with(['lote.producto', 'usuario'])
                ->whereIn('tipo_movimiento', ['SALIDA_MERMA', 'SALIDA_RETIRADO']);

            if ($request->fecha_inicio && $request->fecha_fin) {
                $query->entreFechas($request->fecha_inicio, $request->fecha_fin);
            }

            $mermas = $query->orderBy('created_at', 'desc')->get();

            $totalMermas = $mermas->sum('cantidad');
            $costoTotalMermas = $mermas->sum('costo_total');

            // Agrupar por producto
            $mermasPorProducto = $mermas->groupBy('lote.producto_id')
                ->map(function ($items, $productoId) {
                    $producto = $items->first()->lote->producto;

                    return [
                        'producto_id' => $productoId,
                        'producto_nombre' => $producto->nombre ?? 'N/A',
                        'codigo_barras' => $producto->codigo_barras ?? 'N/A',
                        'total_unidades' => $items->sum('cantidad'),
                        'costo_total' => $items->sum('costo_total'),
                        'movimientos_count' => $items->count(),
                    ];
                })
                ->sortByDesc('costo_total')
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'mermas' => $mermas,
                    'resumen' => [
                        'total_mermas' => $totalMermas,
                        'costo_total_mermas' => $costoTotalMermas,
                        'por_producto' => $mermasPorProducto,
                    ],
                ],
                'message' => 'Reporte de mermas obtenido correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener reporte de mermas: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reporte: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener reporte de movimientos por usuario
     */
    public function getReportePorUsuario(Request $request, string $usuarioId): JsonResponse
    {
        try {
            $request->validate([
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date',
            ]);

            $query = MovimientoLote::with(['lote.producto', 'sucursal'])
                ->where('usuario_id', $usuarioId);

            if ($request->fecha_inicio && $request->fecha_fin) {
                $query->entreFechas($request->fecha_inicio, $request->fecha_fin);
            }

            $movimientos = $query->orderBy('created_at', 'desc')->get();

            $resumenPorTipo = $movimientos->groupBy('tipo_movimiento')
                ->map(function ($items, $tipo) {
                    return [
                        'tipo' => $tipo,
                        'cantidad' => $items->sum('cantidad'),
                        'movimientos_count' => $items->count(),
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'usuario_id' => $usuarioId,
                    'movimientos' => $movimientos,
                    'resumen_por_tipo' => $resumenPorTipo,
                    'total_movimientos' => $movimientos->count(),
                ],
                'message' => 'Reporte por usuario obtenido correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener reporte por usuario: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reporte: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exportar Kardex a CSV
     */
    public function exportarCsv(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'lote_id' => 'nullable|uuid|exists:lotes,id',
                'producto_id' => 'nullable|uuid|exists:productos,id',
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date',
            ]);

            $query = MovimientoLote::with(['lote.producto', 'usuario', 'sucursal']);

            if ($request->lote_id) {
                $query->where('lote_id', $request->lote_id);
            }

            if ($request->producto_id) {
                $query->whereHas('lote', function ($q) use ($request) {
                    $q->where('producto_id', $request->producto_id);
                });
            }

            if ($request->fecha_inicio && $request->fecha_fin) {
                $query->entreFechas($request->fecha_inicio, $request->fecha_fin);
            }

            $movimientos = $query->orderBy('created_at', 'desc')->get();

            $csvData = [];
            $csvData[] = [
                'Fecha',
                'Tipo Movimiento',
                'Producto',
                'Código',
                'Lote',
                'Cantidad',
                'Stock Anterior',
                'Stock Nuevo',
                'Costo Unitario',
                'Costo Total',
                'Usuario',
                'Sucursal',
                'Observaciones',
            ];

            foreach ($movimientos as $mov) {
                $csvData[] = [
                    $mov->created_at->format('Y-m-d H:i:s'),
                    $mov->tipo_movimiento,
                    $mov->lote->producto->nombre ?? 'N/A',
                    $mov->lote->producto->codigo_barras ?? 'N/A',
                    $mov->lote->numero_lote,
                    $mov->cantidad,
                    $mov->stock_anterior,
                    $mov->stock_nuevo,
                    $mov->costo_unitario,
                    $mov->costo_total,
                    $mov->usuario->nombre ?? 'N/A',
                    $mov->sucursal->nombre ?? 'N/A',
                    $mov->observaciones ?? '',
                ];
            }

            $filename = 'kardex_'.date('Ymd_His').'.csv';

            return response()->streamDownload(function () use ($csvData) {
                $out = fopen('php://output', 'w');
                foreach ($csvData as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);

        } catch (\Exception $e) {
            Log::error('Error al exportar Kardex a CSV: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al exportar: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exportar reporte de movimientos a CSV
     */
    public function exportarMovimientosCsv(Request $request): JsonResponse
    {
        try {
            $query = MovimientoLote::with(['lote.producto', 'usuario', 'sucursal']);

            if ($request->fecha_inicio && $request->fecha_fin) {
                $query->entreFechas($request->fecha_inicio, $request->fecha_fin);
            }

            if ($request->tipo_movimiento) {
                $query->where('tipo_movimiento', $request->tipo_movimiento);
            }

            if ($request->sucursal_id) {
                $query->where('sucursal_id', $request->sucursal_id);
            }

            $movimientos = $query->orderBy('created_at', 'desc')->get();

            $csvData = [];
            $csvData[] = [
                'Fecha',
                'Tipo',
                'Producto',
                'Lote',
                'Cantidad',
                'Costo Total',
                'Usuario',
                'Sucursal',
            ];

            foreach ($movimientos as $mov) {
                $csvData[] = [
                    $mov->created_at->format('Y-m-d H:i:s'),
                    $mov->tipo_movimiento,
                    $mov->lote->producto->nombre ?? 'N/A',
                    $mov->lote->numero_lote,
                    $mov->cantidad,
                    $mov->costo_total,
                    $mov->usuario->nombre ?? 'N/A',
                    $mov->sucursal->nombre ?? 'N/A',
                ];
            }

            $filename = 'movimientos_'.date('Ymd_His').'.csv';

            return response()->streamDownload(function () use ($csvData) {
                $out = fopen('php://output', 'w');
                foreach ($csvData as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);

        } catch (\Exception $e) {
            Log::error('Error al exportar movimientos a CSV: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al exportar: '.$e->getMessage(),
            ], 500);
        }
    }
}
