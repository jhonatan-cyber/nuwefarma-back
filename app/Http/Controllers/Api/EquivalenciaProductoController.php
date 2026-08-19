<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EquivalenciaProducto;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class EquivalenciaProductoController extends Controller
{
    /**
     * Listar las equivalencias registradas (filtrables por producto o tipo).
     */
    public function index(Request $request): JsonResponse
    {
        $query = EquivalenciaProducto::with(['producto', 'productoEquivalente']);

        if ($request->producto_id) {
            $query->where(function ($q) use ($request) {
                $q->where('producto_id', $request->producto_id)
                    ->orWhere('producto_equivalente_id', $request->producto_id);
            });
        }

        if ($request->tipo) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->estado !== null) {
            $query->where('estado', $request->estado);
        }

        $query->orderByDesc('created_at');
        $perPage = min($request->per_page ?? 15, 100);

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage)->through(fn ($eq) => $this->formatear($eq)),
        ], Response::HTTP_OK);
    }

    /**
     * Crear una relación de equivalencia / sustitución.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'producto_id' => ['required', 'exists:productos,id'],
            'producto_equivalente_id' => ['required', 'exists:productos,id', 'different:producto_id'],
            'tipo' => ['nullable', Rule::in([
                EquivalenciaProducto::TIPO_EQUIVALENTE,
                EquivalenciaProducto::TIPO_GENERICO,
                EquivalenciaProducto::TIPO_SUSTITUTO,
            ])],
            'factor_conversion' => ['nullable', 'numeric', 'gt:0'],
            'notas' => ['nullable', 'string', 'max:1000'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ]);

        $existe = EquivalenciaProducto::query()
            ->where(function ($q) use ($validated) {
                $q->where('producto_id', $validated['producto_id'])
                    ->where('producto_equivalente_id', $validated['producto_equivalente_id']);
            })
            ->orWhere(function ($q) use ($validated) {
                $q->where('producto_id', $validated['producto_equivalente_id'])
                    ->where('producto_equivalente_id', $validated['producto_id']);
            })
            ->exists();

        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una equivalencia entre estos productos',
                'errors' => ['equivalencia' => ['La relación ya está registrada (en cualquier sentido)']],
            ], Response::HTTP_CONFLICT);
        }

        $equivalencia = EquivalenciaProducto::create([
            ...$validated,
            'tipo' => $validated['tipo'] ?? EquivalenciaProducto::TIPO_EQUIVALENTE,
            'estado' => $validated['estado'] ?? 'activo',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Equivalencia registrada exitosamente',
            'data' => $this->formatear($equivalencia->load(['producto', 'productoEquivalente'])),
        ], Response::HTTP_CREATED);
    }

    /**
     * Mostrar una equivalencia.
     */
    public function show(EquivalenciaProducto $equivalencia): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->formatear($equivalencia->load(['producto', 'productoEquivalente'])),
        ], Response::HTTP_OK);
    }

    /**
     * Eliminar una equivalencia.
     */
    public function destroy(EquivalenciaProducto $equivalencia): JsonResponse
    {
        $equivalencia->delete();

        return response()->json([
            'success' => true,
            'message' => 'Equivalencia eliminada exitosamente',
        ], Response::HTTP_OK);
    }

    /**
     * Equivalentes sugeridos de un producto (mismo principio activo),
     * excluyendo los ya vinculados, sin insinuar la decisión clínica.
     */
    public function sugeridos(Request $request, Producto $producto): JsonResponse
    {
        $vinculados = EquivalenciaProducto::query()
            ->where('producto_id', $producto->id)
            ->orWhere('producto_equivalente_id', $producto->id)
            ->get()
            ->map(fn ($eq) => $eq->producto_id === $producto->id
                ? $eq->producto_equivalente_id
                : $eq->producto_id)
            ->push($producto->id)
            ->all();

        $sugeridos = Producto::query()
            ->where('estado', 'activo')
            ->where(function ($q) use ($producto) {
                $q->whereNotNull('principio_activo')
                    ->where('principio_activo', $producto->principio_activo);
            })
            ->whereNotIn('id', $vinculados)
            ->limit(min($request->limit ?? 10, 50))
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'producto' => [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'principio_activo' => $producto->principio_activo,
                ],
                'sugeridos' => $sugeridos->map(fn (Producto $p) => [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'principio_activo' => $p->principio_activo,
                    'concentracion' => $p->concentracion,
                    'forma_farmaceutica' => $p->forma_farmaceutica,
                    'laboratorio' => $p->laboratorio,
                    'condicion_venta' => $p->condicion_venta,
                ])->values(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatear(EquivalenciaProducto $eq): array
    {
        return [
            'id' => $eq->id,
            'tipo' => $eq->tipo,
            'tipo_label' => $eq->tipo_label,
            'factor_conversion' => $eq->factor_conversion,
            'notas' => $eq->notas,
            'estado' => $eq->estado,
            'producto' => $eq->producto ? [
                'id' => $eq->producto->id,
                'nombre' => $eq->producto->nombre,
                'principio_activo' => $eq->producto->principio_activo,
                'laboratorio' => $eq->producto->laboratorio,
            ] : null,
            'producto_equivalente' => $eq->productoEquivalente ? [
                'id' => $eq->productoEquivalente->id,
                'nombre' => $eq->productoEquivalente->nombre,
                'principio_activo' => $eq->productoEquivalente->principio_activo,
                'laboratorio' => $eq->productoEquivalente->laboratorio,
            ] : null,
            'created_at' => $eq->created_at,
        ];
    }
}