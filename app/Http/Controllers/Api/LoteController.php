<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lote;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoteController extends Controller
{
    private InventarioService $inventarioService;

    public function __construct(InventarioService $inventarioService)
    {
        $this->inventarioService = $inventarioService;
    }

    /**
     * Listar todos los lotes con filtros
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Lote::with(['producto', 'proveedor'])
                ->when($request->producto_id, fn($q, $v) => $q->where('producto_id', $v))
                ->when($request->estado, fn($q, $v) => $q->where('estado', $v))
                ->when($request->disponible, fn($q) => $q->disponibles())
                ->when($request->proximos_vencer, fn($q, $v) => $q->proximosAVencer((int) $v))
                ->when($request->stock_bajo, fn($q) => $q->stockBajo())
                ->when($request->search, fn($q, $v) => $q->where('numero_lote', 'like', "%{$v}%"))
                ->orderBy('created_at', 'desc');

            $lotes = $query->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $lotes,
                'message' => 'Lotes obtenidos correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al listar lotes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener lotes: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener un lote específico
     */
    public function show(string $id): JsonResponse
    {
        try {
            $lote = Lote::with(['producto', 'proveedor', 'movimientos.usuario'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $lote,
                'message' => 'Lote obtenido correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener lote: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener lote: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear un nuevo lote
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'producto_id' => 'required|uuid|exists:productos,id',
                'numero_lote' => 'required|string|max:100',
                'fecha_vencimiento' => 'required|date|after:today',
                'stock' => 'required|integer|min:0',
                'stock_minimo' => 'nullable|integer|min:0',
                'stock_maximo' => 'nullable|integer|min:0',
                'precio_costo' => 'nullable|numeric|min:0',
                'proveedor_id' => 'nullable|uuid|exists:proveedores,id',
                'ubicacion_bodega' => 'nullable|string|max:100',
                'notas' => 'nullable|string',
            ]);

            $lote = $this->inventarioService->crearLote($validated);

            return response()->json([
                'success' => true,
                'data' => $lote->load(['producto', 'proveedor']),
                'message' => 'Lote creado correctamente',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear lote: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear lote: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar un lote
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $lote = Lote::findOrFail($id);

            $validated = $request->validate([
                'numero_lote' => 'sometimes|string|max:100',
                'fecha_vencimiento' => 'sometimes|date',
                'stock' => 'sometimes|integer|min:0',
                'stock_minimo' => 'sometimes|integer|min:0',
                'stock_maximo' => 'sometimes|integer|min:0',
                'precio_costo' => 'sometimes|numeric|min:0',
                'ubicacion_bodega' => 'sometimes|string|max:100',
                'notas' => 'sometimes|string',
            ]);

            $lote->update($validated);

            // Recalcular estado si cambió el stock
            if (isset($validated['stock'])) {
                if ($lote->stock == 0) {
                    $lote->estado = 'agotado';
                } elseif ($lote->stock <= $lote->stock_minimo) {
                    $lote->estado = 'parcial';
                } else {
                    $lote->estado = 'disponible';
                }
                $lote->save();
            }

            return response()->json([
                'success' => true,
                'data' => $lote->load(['producto', 'proveedor']),
                'message' => 'Lote actualizado correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar lote: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar lote: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar un lote (soft delete)
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $lote = Lote::findOrFail($id);

            if ($lote->stock > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar un lote con stock. Primero agote o transfiera el stock.',
                ], 400);
            }

            $lote->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lote eliminado correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar lote: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar lote: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener lotes disponibles para un producto (FEFO)
     */
    public function getLotesDisponibles(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'producto_id' => 'required|uuid|exists:productos,id',
                'cantidad' => 'nullable|integer|min:1',
            ]);

            $lotes = $this->inventarioService->getLotesDisponibles(
                $request->producto_id,
                $request->cantidad
            );

            return response()->json([
                'success' => true,
                'data' => $lotes,
                'message' => 'Lotes disponibles obtenidos correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener lotes disponibles: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener lotes disponibles: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Agregar stock a un lote (entrada por compra)
     */
    public function agregarStock(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'cantidad' => 'required|integer|min:1',
                'precio_costo' => 'required|numeric|min:0',
                'compra_id' => 'nullable|uuid',
                'documento_numero' => 'nullable|string',
                'observaciones' => 'nullable|string',
            ]);

            $resultado = $this->inventarioService->agregarStock(
                $id,
                $request->cantidad,
                $request->precio_costo,
                [
                    'tipo' => 'Compra',
                    'id' => $request->compra_id,
                    'numero' => $request->documento_numero,
                    'observaciones' => $request->observaciones,
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $resultado,
                'message' => 'Stock agregado correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al agregar stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Descontar stock de un lote específico
     */
    public function descontarStock(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'cantidad' => 'required|integer|min:1',
                'venta_id' => 'nullable|uuid',
                'documento_numero' => 'nullable|string',
                'observaciones' => 'nullable|string',
            ]);

            $resultado = $this->inventarioService->descontarStockDeLote(
                $id,
                $request->cantidad,
                [
                    'tipo' => 'Venta',
                    'id' => $request->venta_id,
                    'numero' => $request->documento_numero,
                    'observaciones' => $request->observaciones,
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $resultado,
                'message' => 'Stock descontado correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al descontar stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al descontar stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marcar lote como vencido
     */
    public function marcarVencido(string $id): JsonResponse
    {
        try {
            $resultado = $this->inventarioService->marcarVencido($id);

            return response()->json([
                'success' => true,
                'data' => $resultado,
                'message' => 'Lote marcado como vencido',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al marcar lote como vencido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar lote como vencido: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener resumen de inventario
     */
    public function getResumenInventario(): JsonResponse
    {
        try {
            $resumen = $this->inventarioService->getResumenInventario();

            return response()->json([
                'success' => true,
                'data' => $resumen,
                'message' => 'Resumen de inventario obtenido correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener resumen de inventario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen de inventario: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener productos con stock bajo
     */
    public function getProductosStockBajo(): JsonResponse
    {
        try {
            $productos = $this->inventarioService->getProductosStockBajo();

            return response()->json([
                'success' => true,
                'data' => $productos,
                'message' => 'Productos con stock bajo obtenidos correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener productos con stock bajo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos con stock bajo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener productos próximos a vencer
     */
    public function getProductosProximosAVencer(Request $request): JsonResponse
    {
        try {
            $dias = $request->dias ?? 60;
            $productos = $this->inventarioService->getProductosProximosAVencer((int) $dias);

            return response()->json([
                'success' => true,
                'data' => $productos,
                'message' => 'Productos próximos a vencer obtenidos correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener productos próximos a vencer: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos próximos a vencer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transferir stock entre lotes
     */
    public function transferirEntreLotes(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'lote_origen_id' => 'required|uuid|exists:lotes,id',
                'lote_destino_id' => 'required|uuid|exists:lotes,id',
                'cantidad' => 'required|integer|min:1',
            ]);

            $resultado = $this->inventarioService->transferirEntreLotes(
                $request->lote_origen_id,
                $request->lote_destino_id,
                $request->cantidad
            );

            return response()->json([
                'success' => true,
                'data' => $resultado,
                'message' => 'Transferencia realizada correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al transferir stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al transferir stock: ' . $e->getMessage(),
            ], 500);
        }
    }
}
