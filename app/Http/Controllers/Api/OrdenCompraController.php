<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrdenCompra;
use App\Services\OrdenCompraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrdenCompraController extends Controller
{
    public function __construct(private OrdenCompraService $ordenCompraService) {}

    /**
     * Listar órdenes/solicitudes de compra.
     */
    public function index(Request $request): JsonResponse
    {
        $query = OrdenCompra::with(['proveedor', 'sucursal']);

        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        if ($request->tipo) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->proveedor_id) {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        if ($request->q) {
            $query->where('numero_orden', 'like', "%{$request->q}%");
        }

        if ($request->sucursal_id) {
            $query->where('sucursal_id', $request->sucursal_id);
        } elseif ($request->user()->sucursal_id) {
            $query->where('sucursal_id', $request->user()->sucursal_id);
        }

        $query->orderByDesc('created_at');
        $perPage = min($request->per_page ?? 15, 100);

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage)->through(fn ($o) => $this->formatear($o, true)),
        ], Response::HTTP_OK);
    }

    /**
     * Crear una solicitud u orden de compra.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->all();
        $data['sucursal_id'] = $request->user()->sucursal_id;

        $orden = $this->ordenCompraService->crear($data);

        return response()->json([
            'success' => true,
            'message' => 'Orden de compra creada exitosamente',
            'data' => $this->formatear($orden->load('productos.producto.lotes'), false),
        ], Response::HTTP_CREATED);
    }

    /**
     * Mostrar una orden de compra.
     */
    public function show(Request $request, OrdenCompra $orden): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->formatear(
                $orden->load(['proveedor', 'sucursal', 'usuario', 'aprobadoPor', 'recibidoPor', 'productos.producto.lotes']),
                false
            ),
        ], Response::HTTP_OK);
    }

    /**
     * Aprobar una solicitud de compra.
     */
    public function aprobar(Request $request, OrdenCompra $orden): JsonResponse
    {
        $validated = $request->validate([
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);

        $orden = $this->ordenCompraService->aprobar($orden, $request->user()->id, $validated['notas'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Solicitud aprobada',
            'data' => $this->formatear($orden, false),
        ], Response::HTTP_OK);
    }

    /**
     * Rechazar una solicitud de compra.
     */
    public function rechazar(Request $request, OrdenCompra $orden): JsonResponse
    {
        $validated = $request->validate([
            'motivo' => ['required', 'string', 'max:1000'],
        ]);

        $orden = $this->ordenCompraService->rechazar($orden, $request->user()->id, $validated['motivo']);

        return response()->json([
            'success' => true,
            'message' => 'Solicitud rechazada',
            'data' => $this->formatear($orden, false),
        ], Response::HTTP_OK);
    }

    /**
     * Enviar la orden al proveedor.
     */
    public function enviar(Request $request, OrdenCompra $orden): JsonResponse
    {
        $orden = $this->ordenCompraService->enviar($orden, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Orden enviada al proveedor',
            'data' => $this->formatear($orden, false),
        ], Response::HTTP_OK);
    }

    /**
     * Recibir mercadería (total o parcial) de la orden.
     */
    public function recibir(Request $request, OrdenCompra $orden): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.orden_producto_id' => ['required', 'exists:orden_compra_productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'gt:0'],
        ]);

        $orden = $this->ordenCompraService->recibir($orden, $validated['items'], $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Recepción registrada',
            'data' => $this->formatear($orden, false),
        ], Response::HTTP_OK);
    }

    /**
     * Cancelar una orden.
     */
    public function cancelar(Request $request, OrdenCompra $orden): JsonResponse
    {
        $validated = $request->validate([
            'motivo' => ['nullable', 'string', 'max:1000'],
        ]);

        $orden = $this->ordenCompraService->cancelar($orden, $request->user()->id, $validated['motivo'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Orden cancelada',
            'data' => $this->formatear($orden, false),
        ], Response::HTTP_OK);
    }

    /**
     * Sugerencias de reposición de inventario.
     */
    public function sugerencias(Request $request): JsonResponse
    {
        $sugerencias = $this->ordenCompraService->sugerirReposicion($request->user()->sucursal_id);

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Lista de reposición sugerida. Debe ser revisada y aprobada por el responsable antes de emitir una orden.',
                'sugerencias' => $sugerencias,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Historial de precios de un producto por proveedor para comparar costos.
     */
    public function historialPrecios(Request $request, \App\Models\Producto $producto): JsonResponse
    {
        $query = \App\Models\CompraProducto::with(['compra.proveedor'])
            ->where('producto_id', $producto->id);

        if ($request->proveedor_id) {
            $query->whereHas('compra', fn ($q) => $q->where('proveedor_id', $request->proveedor_id));
        }

        $query->orderByDesc('created_at')->limit(min($request->limit ?? 20, 50));

        $compras = $query->get()->map(fn ($cp) => [
            'compra_id' => $cp->compra_id,
            'numero_compra' => $cp->compra?->numero_compra,
            'fecha' => $cp->compra?->fecha_compra?->toDateString(),
            'proveedor' => $cp->compra?->proveedor ? [
                'id' => $cp->compra->proveedor->id,
                'nombre' => $cp->compra->proveedor->nombre,
                'nit' => $cp->compra->proveedor->nit,
            ] : null,
            'cantidad' => $cp->cantidad,
            'precio_unitario' => (float) $cp->precio_unitario,
        ])->values();

        // Mejor precio histórico por proveedor.
        $mejoresPorProveedor = $compras->groupBy('proveedor.id')->map(function ($items) {
            $mejor = $items->sortBy('precio_unitario')->first();

            return [
                'proveedor' => $mejor['proveedor'],
                'mejor_precio' => $mejor['precio_unitario'],
                'ultima_compra' => $items->sortByDesc('fecha')->first()['fecha'],
                'compras' => $items->count(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'producto' => [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'codigo' => $producto->codigo_barras,
                ],
                'historial' => $compras,
                'comparativa_por_proveedor' => $mejoresPorProveedor,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatear(OrdenCompra $o, bool $resumido): array
    {
        return [
            'id' => $o->id,
            'numero_orden' => $o->numero_orden,
            'tipo' => $o->tipo,
            'prioridad' => $o->prioridad,
            'estado' => $o->estado,
            'fecha_solicitud' => $o->fecha_solicitud?->toDateString(),
            'fecha_estimada' => $o->fecha_estimada?->toDateString(),
            'fecha_recepcion' => $o->fecha_recepcion?->toDateString(),
            'subtotal' => (float) $o->subtotal,
            'descuento' => (float) $o->descuento,
            'impuestos' => (float) $o->impuestos,
            'total' => (float) $o->total,
            'notas' => $o->notas,
            'motivo_rechazo' => $o->motivo_rechazo,
            'proveedor' => $o->proveedor ? [
                'id' => $o->proveedor->id,
                'nombre' => $o->proveedor->nombre,
                'nit' => $o->proveedor->nit,
            ] : null,
            'sucursal' => $o->sucursal ? [
                'id' => $o->sucursal->id,
                'nombre' => $o->sucursal->nombre,
            ] : null,
            'productos' => $o->productos->map(fn ($p) => [
                'id' => $p->id,
                'producto_id' => $p->producto_id,
                'producto' => $p->producto ? [
                    'id' => $p->producto->id,
                    'nombre' => $p->producto->nombre,
                    'codigo' => $p->producto->codigo_barras,
                    'stock_actual' => $p->producto->stock_actual,
                ] : null,
                'cantidad' => (float) $p->cantidad,
                'cantidad_recibida' => (float) $p->cantidad_recibida,
                'pendiente' => $p->getPendienteRecibir(),
                'precio_unitario' => (float) $p->precio_unitario,
                'descuento' => (float) $p->descuento,
                'impuesto' => (float) $p->impuesto,
                'subtotal' => (float) $p->subtotal,
                'estado' => (float) $p->cantidad_recibida >= (float) $p->cantidad
                    ? 'recibido'
                    : ((float) $p->cantidad_recibida > 0 ? 'parcial' : 'pendiente'),
            ])->values(),
            'lotes' => $o->productos->flatMap(function ($p) {
                return $p->relationLoaded('lote') && $p->lote ? [[
                    'id' => $p->lote->id,
                    'numero_lote' => $p->lote->numero_lote,
                    'producto' => $p->producto?->nombre,
                    'stock' => $p->lote->stock,
                    'fecha_vencimiento' => $p->lote->fecha_vencimiento?->toDateString(),
                ]] : [];
            })->values(),
            'created_at' => $o->created_at,
            'updated_at' => $o->updated_at,
        ];
    }
}