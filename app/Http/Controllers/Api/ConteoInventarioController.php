<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConteoInventario;
use App\Models\ConteoInventarioItem;
use App\Models\Lote;
use App\Services\ConteoInventarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ConteoInventarioController extends Controller
{
    public function __construct(private ConteoInventarioService $conteoInventarioService) {}

    /**
     * Listar conteos.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ConteoInventario::with(['sucursal']);

        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        if ($request->tipo) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->q) {
            $query->where('numero_conteo', 'like', "%{$request->q}%");
        }

        $query->orderByDesc('created_at');
        $perPage = min($request->per_page ?? 15, 100);

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ], Response::HTTP_OK);
    }

    /**
     * Crear un conteo. Genera los ítems a partir de los lotes con stock.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo' => ['required', Rule::in([ConteoInventario::TIPO_FISICO, ConteoInventario::TIPO_CICLICO])],
            'fecha_programada' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'responsable_id' => ['nullable', 'exists:usuarios,id'],
            'producto_ids' => ['nullable', 'array'],
            'producto_ids.*' => ['exists:productos,id'],
        ]);

        $conteo = ConteoInventario::create([
            'numero_conteo' => ConteoInventario::generateNumeroConteo(),
            'sucursal_id' => $request->user()->sucursal_id,
            'tipo' => $validated['tipo'],
            'estado' => ConteoInventario::ESTADO_PENDIENTE,
            'fecha_programada' => $validated['fecha_programada'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
            'responsable_id' => $validated['responsable_id'] ?? $request->user()->id,
        ]);

        $lotesQuery = Lote::query()
            ->where('stock', '>', 0)
            ->where('estado', 'disponible')
            ->where('fecha_vencimiento', '>', now())
            ->with('producto');

        if (! empty($validated['producto_ids'])) {
            $lotesQuery->whereIn('producto_id', $validated['producto_ids']);
        }

        $items = [];
        foreach ($lotesQuery->get() as $lote) {
            $items[] = ConteoInventarioItem::create([
                'conteo_id' => $conteo->id,
                'producto_id' => $lote->producto_id,
                'lote_id' => $lote->id,
                'stock_sistema' => $lote->stock_disponible,
                'stock_fisico' => null,
                'diferencia' => 0,
                'estado' => ConteoInventarioItem::ESTADO_PENDIENTE,
            ]);
        }

        $conteo->update([
            'total_items' => count($items),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conteo creado con '.count($items).' ítems',
            'data' => $conteo->load(['items.producto', 'sucursal']),
        ], Response::HTTP_CREATED);
    }

    /**
     * Mostrar un conteo con sus ítems.
     */
    public function show(ConteoInventario $conteo): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $conteo->load(['items.lote', 'items.producto', 'items.contadoPor', 'sucursal', 'responsable', 'cerradoPor']),
        ], Response::HTTP_OK);
    }

    /**
     * Registrar el conteo físico de un ítem.
     */
    public function registrarConteo(Request $request, ConteoInventario $conteo, ConteoInventarioItem $item): JsonResponse
    {
        if ($conteo->estado !== ConteoInventario::ESTADO_PENDIENTE
            && $conteo->estado !== ConteoInventario::ESTADO_EN_PROCESO) {
            return $this->conteoEstadoInvalido();
        }

        if ($item->conteo_id !== $conteo->id) {
            return response()->json([
                'success' => false,
                'message' => 'El ítem no pertenece al conteo',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validate([
            'stock_fisico' => ['required', 'integer', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $item->update([
            'stock_fisico' => $validated['stock_fisico'],
            'diferencia' => $validated['stock_fisico'] - $item->stock_sistema,
            'observaciones' => $validated['observaciones'] ?? $item->observaciones,
            'estado' => ConteoInventarioItem::ESTADO_CONTADO,
            'contado_por_id' => $request->user()->id,
        ]);

        $conteo->recalcularResumen();

        return response()->json([
            'success' => true,
            'message' => 'Conteo registrado',
            'data' => $item->load('producto'),
        ], Response::HTTP_OK);
    }

    /**
     * Cerrar el conteo y aplicar las diferencias.
     */
    public function cerrar(Request $request, ConteoInventario $conteo): JsonResponse
    {
        $result = $this->conteoInventarioService->cerrar($conteo, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Conteo cerrado y diferencias aplicadas',
            'data' => [
                'conteo' => $result['conteo'],
                'ajustes' => $result['ajustes'],
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Cancelar un conteo pendiente.
     */
    public function cancelar(Request $request, ConteoInventario $conteo): JsonResponse
    {
        if ($conteo->estado === ConteoInventario::ESTADO_CERRADO) {
            return $this->conteoEstadoInvalido();
        }

        $conteo->update([
            'estado' => ConteoInventario::ESTADO_CANCELADO,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conteo cancelado',
            'data' => $conteo->fresh(),
        ], Response::HTTP_OK);
    }

    private function conteoEstadoInvalido(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'El conteo no está en un estado válido para esta operación',
        ], Response::HTTP_CONFLICT);
    }
}