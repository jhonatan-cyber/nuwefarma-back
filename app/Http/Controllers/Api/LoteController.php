<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lote;
use App\Models\MovimientoLote;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoteController extends Controller
{
    public function __construct(private InventarioService $inventarioService) {}

    public function index(Request $request)
    {
        try {
            $query = Lote::with(['producto', 'proveedor'])
                ->when($request->producto_id, fn ($q, $v) => $q->where('producto_id', $v))
                ->when($request->estado, fn ($q, $v) => $q->where('estado', $v))
                ->when($request->disponible, fn ($q) => $q->disponibles())
                ->when($request->proximos_vencer, fn ($q, $v) => $q->proximosAVencer((int) $v))
                ->when($request->stock_bajo, fn ($q) => $q->stockBajo())
                ->when($request->search, fn ($q, $v) => $q->where('numero_lote', 'like', "%{$v}%"))
                ->orderBy('created_at', 'desc');

            $lotes = $query->paginate($request->per_page ?? 20);

            return $this->success($lotes, 'Lotes obtenidos correctamente');
        } catch (\Exception $e) {
            Log::error('Error al listar lotes: '.$e->getMessage());

            return $this->error('Error al obtener lotes: '.$e->getMessage());
        }
    }

    public function show(string $id)
    {
        try {
            $lote = Lote::with(['producto', 'proveedor', 'movimientos.usuario'])
                ->findOrFail($id);

            return $this->success($lote, 'Lote obtenido correctamente');
        } catch (\Exception $e) {
            Log::error('Error al obtener lote: '.$e->getMessage());

            return $this->notFound('Lote no encontrado');
        }
    }

    public function store(Request $request)
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

            return $this->created(
                $lote->load(['producto', 'proveedor']),
                'Lote creado correctamente'
            );
        } catch (\Exception $e) {
            Log::error('Error al crear lote: '.$e->getMessage());

            return $this->error('Error al crear lote: '.$e->getMessage());
        }
    }

    public function update(Request $request, string $id)
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

            // La identidad del lote es inmutable una vez que registra movimientos
            if ($lote->movimientos()->exists()) {
                if (isset($validated['numero_lote']) && $validated['numero_lote'] !== $lote->numero_lote) {
                    throw new \Exception('No se puede cambiar el número de lote de un lote con movimientos registrados');
                }
            }

            // El stock solo se modifica a través de movimientos auditables
            if (isset($validated['stock'])) {
                $nuevoStock = (int) $validated['stock'];
                $diferencia = $nuevoStock - (int) $lote->stock;

                if ($diferencia !== 0) {
                    $documento = [
                        'tipo' => 'AjusteInventario',
                        'id' => $lote->id,
                        'numero' => $lote->numero_lote,
                        'observaciones' => 'Ajuste directo de stock desde edición de lote',
                    ];

                    if ($diferencia > 0) {
                        $this->inventarioService->agregarStock(
                            $lote->id,
                            $diferencia,
                            $lote->precio_costo_promedio ?? $lote->precio_costo ?? 0,
                            $documento,
                            MovimientoLote::AJUSTE_POSITIVO
                        );
                    } else {
                        $this->inventarioService->descontarStockDeLote(
                            $lote->id,
                            abs($diferencia),
                            $documento,
                            MovimientoLote::AJUSTE_NEGATIVO
                        );
                    }
                }

                unset($validated['stock']);
            }

            $lote->update($validated);
            $lote->refresh();

            return $this->success(
                $lote->load(['producto', 'proveedor']),
                'Lote actualizado correctamente'
            );
        } catch (\Exception $e) {
            Log::error('Error al actualizar lote: '.$e->getMessage());

            return $this->error('Error al actualizar lote: '.$e->getMessage());
        }
    }

    public function destroy(string $id)
    {
        try {
            $lote = Lote::findOrFail($id);

            if ($lote->stock > 0) {
                return $this->error('No se puede eliminar un lote con stock. Primero agote o transfiera el stock.', null, 400);
            }

            $lote->delete();

            return $this->noContent('Lote eliminado correctamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar lote: '.$e->getMessage());

            return $this->error('Error al eliminar lote: '.$e->getMessage());
        }
    }

    public function getLotesDisponibles(Request $request)
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

            return $this->success($lotes, 'Lotes disponibles obtenidos correctamente');
        } catch (\Exception $e) {
            Log::error('Error al obtener lotes disponibles: '.$e->getMessage());

            return $this->error('Error al obtener lotes disponibles: '.$e->getMessage());
        }
    }

    public function agregarStock(Request $request, string $id)
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

            return $this->success($resultado, 'Stock agregado correctamente');
        } catch (\Exception $e) {
            Log::error('Error al agregar stock: '.$e->getMessage());

            return $this->error('Error al agregar stock: '.$e->getMessage());
        }
    }

    public function descontarStock(Request $request, string $id)
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

            return $this->success($resultado, 'Stock descontado correctamente');
        } catch (\Exception $e) {
            Log::error('Error al descontar stock: '.$e->getMessage());

            return $this->error('Error al descontar stock: '.$e->getMessage());
        }
    }

    public function marcarVencido(string $id)
    {
        try {
            $resultado = $this->inventarioService->marcarVencido($id);

            return $this->success($resultado, 'Lote marcado como vencido');
        } catch (\Exception $e) {
            Log::error('Error al marcar lote como vencido: '.$e->getMessage());

            return $this->error('Error al marcar lote como vencido: '.$e->getMessage());
        }
    }

    public function getResumenInventario()
    {
        try {
            $resumen = $this->inventarioService->getResumenInventario();

            return $this->success($resumen, 'Resumen de inventario obtenido correctamente');
        } catch (\Exception $e) {
            Log::error('Error al obtener resumen de inventario: '.$e->getMessage());

            return $this->error('Error al obtener resumen de inventario: '.$e->getMessage());
        }
    }

    public function getProductosStockBajo()
    {
        try {
            $productos = $this->inventarioService->getProductosStockBajo();

            return $this->success($productos, 'Productos con stock bajo obtenidos correctamente');
        } catch (\Exception $e) {
            Log::error('Error al obtener productos con stock bajo: '.$e->getMessage());

            return $this->error('Error al obtener productos con stock bajo: '.$e->getMessage());
        }
    }

    public function getProductosProximosAVencer(Request $request)
    {
        try {
            $dias = $request->dias ?? 60;
            $productos = $this->inventarioService->getProductosProximosAVencer((int) $dias);

            return $this->success($productos, 'Productos próximos a vencer obtenidos correctamente');
        } catch (\Exception $e) {
            Log::error('Error al obtener productos próximos a vencer: '.$e->getMessage());

            return $this->error('Error al obtener productos próximos a vencer: '.$e->getMessage());
        }
    }

    public function transferirEntreLotes(Request $request)
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

            return $this->success($resultado, 'Transferencia realizada correctamente');
        } catch (\Exception $e) {
            Log::error('Error al transferir stock: '.$e->getMessage());

            return $this->error('Error al transferir stock: '.$e->getMessage());
        }
    }
}
