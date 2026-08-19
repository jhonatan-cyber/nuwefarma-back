<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Receta\RecetaCollection;
use App\Http\Resources\Receta\RecetaResource;
use App\Models\Receta;
use App\Models\RecetaProducto;
use App\Services\DispensacionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class RecetaController extends Controller
{
    public function __construct(private DispensacionService $dispensacionService) {}

    /**
     * Listar recetas con filtros.
     */
    public function index(Request $request)
    {
        $query = Receta::with(['medico', 'paciente']);

        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        if ($request->paciente_id) {
            $query->where('paciente_id', $request->paciente_id);
        }

        if ($request->medico_id) {
            $query->where('medico_id', $request->medico_id);
        }

        if ($request->numero_receta) {
            $query->where('numero_receta', 'like', "%{$request->numero_receta}%");
        }

        if ($request->fecha_inicio) {
            $query->whereDate('fecha_emision', '>=', $request->fecha_inicio);
        }

        if ($request->fecha_fin) {
            $query->whereDate('fecha_emision', '<=', $request->fecha_fin);
        }

        // Solo pendientes/parciales para dispensación abierta.
        if ($request->pendientes_por_dispensar) {
            $query->whereIn('estado', [Receta::ESTADO_PENDIENTE, Receta::ESTADO_PARCIAL]);
        }

        $query->orderByDesc('fecha_emision');

        $perPage = min($request->per_page ?? 15, 100);

        return $this->success(new RecetaCollection($query->paginate($perPage)));
    }

    /**
     * Crear una receta con sus productos prescritos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'medico_id' => ['required', 'exists:medicos,id'],
            'paciente_id' => ['required', 'exists:pacientes,id'],
            'fecha_emision' => ['required', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after:fecha_emision'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'productos' => ['required', 'array', 'min:1'],
            'productos.*.producto_id' => ['required', 'exists:productos,id'],
            'productos.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'productos.*.posologia' => ['nullable', 'string', 'max:500'],
            'productos.*.duracion' => ['nullable', 'string', 'max:100'],
        ]);

        $receta = Receta::create([
            'numero_receta' => Receta::generateNumeroReceta(),
            'medico_id' => $validated['medico_id'],
            'paciente_id' => $validated['paciente_id'],
            'fecha_emision' => $validated['fecha_emision'],
            'fecha_vencimiento' => $validated['fecha_vencimiento'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
            'estado' => Receta::ESTADO_PENDIENTE,
            'usuario_id' => $request->user()->id,
            'sucursal_id' => $request->user()->sucursal_id,
        ]);

        foreach ($validated['productos'] as $producto) {
            RecetaProducto::create([
                'receta_id' => $receta->id,
                'producto_id' => $producto['producto_id'],
                'cantidad_prescrita' => $producto['cantidad'],
                'cantidad_dispensada' => 0,
                'posologia' => $producto['posologia'] ?? null,
                'duracion' => $producto['duracion'] ?? null,
                'estado' => RecetaProducto::ESTADO_PENDIENTE,
            ]);
        }

        return $this->created(
            new RecetaResource($receta->load(['medico', 'paciente', 'productos.producto'])),
            'Receta creada exitosamente'
        );
    }

    /**
     * Mostrar una receta con sus productos.
     */
    public function show(Receta $receta)
    {
        return $this->success(
            new RecetaResource($receta->load(['medico', 'paciente', 'productos.producto']))
        );
    }

    /**
     * Dispensar (total o parcial) los productos de la receta.
     */
    public function dispensar(Request $request, Receta $receta)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.receta_producto_id' => ['required', 'exists:receta_productos,id'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'items.*.lote_id' => ['nullable', 'exists:lotes,id'],
            'items.*.observaciones' => ['nullable', 'string', 'max:500'],
            'autorizacion_controlado' => ['nullable', 'string', 'max:100'],
        ]);

        $dispensada = $this->dispensacionService->dispensar($receta, $validated['items'], [
            'autorizacion_controlado' => $validated['autorizacion_controlado'] ?? null,
            'usuario_id' => $request->user()->id,
        ]);

        return $this->success(
            new RecetaResource($dispensada),
            'Dispensación registrada exitosamente'
        );
    }

    /**
     * Anular una receta pendiente o parcial.
     */
    public function anular(Request $request, Receta $receta)
    {
        $validated = $request->validate([
            'motivo' => ['nullable', 'string', 'max:500'],
        ]);

        if ($receta->estado === Receta::ESTADO_ANULADA) {
            return $this->conflict('La receta ya está anulada');
        }

        $receta->update([
            'estado' => Receta::ESTADO_ANULADA,
            'observaciones' => $validated['motivo']
                ? trim(($receta->observaciones ?? '')." | Anulada: {$validated['motivo']}")
                : $receta->observaciones,
        ]);

        $receta->productos()->update(['estado' => RecetaProducto::ESTADO_ANULADO]);

        return $this->success(
            new RecetaResource($receta->load(['medico', 'paciente', 'productos.producto'])),
            'Receta anulada exitosamente'
        );
    }

    /**
     * Marcar como vencidas las recetas con fecha de vencimiento pasada.
     */
    public function marcarVencidas()
    {
        $actualizadas = Receta::query()
            ->whereIn('estado', [Receta::ESTADO_PENDIENTE, Receta::ESTADO_PARCIAL])
            ->where('fecha_vencimiento', '<', now()->toDateString())
            ->update(['estado' => Receta::ESTADO_VENCIDA]);

        return $this->success(
            ['actualizadas' => $actualizadas],
            'Recetas vencidas actualizadas'
        );
    }
}