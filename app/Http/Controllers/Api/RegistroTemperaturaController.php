<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegistroTemperatura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class RegistroTemperaturaController extends Controller
{
    /**
     * Listar registros de temperatura con filtros por fecha, rango y sucursal.
     */
    public function index(Request $request): JsonResponse
    {
        $query = RegistroTemperatura::with(['sucursal']);

        if ($request->sucursal_id) {
            $query->where('sucursal_id', $request->sucursal_id);
        }

        if ($request->fecha_inicio) {
            $query->where('registrado_en', '>=', $request->fecha_inicio.' 00:00:00');
        }

        if ($request->fecha_fin) {
            $query->where('registrado_en', '<=', $request->fecha_fin.' 23:59:59');
        }

        if ($request->ubicacion) {
            $query->where('ubicacion', 'like', "%{$request->ubicacion}%");
        }

        if ($request->fuera_rango) {
            $query->where('dentro_rango', false);
        }

        $query->orderByDesc('registrado_en');
        $perPage = min($request->per_page ?? 15, 100);

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage)->through(fn ($r) => $this->formatear($r)),
        ], Response::HTTP_OK);
    }

    /**
     * Registrar una lectura de temperatura.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo_registro' => ['nullable', Rule::in(['manual', 'automático', 'automatico', 'sensor'])],
            'ubicacion' => ['nullable', 'string', 'max:150'],
            'dispositivo' => ['nullable', 'string', 'max:150'],
            'temperatura' => ['required', 'numeric', 'between:-30,70'],
            'humedad' => ['nullable', 'numeric', 'between:0,100'],
            'temp_minima_aceptable' => ['nullable', 'numeric', 'between:-30,70'],
            'temp_maxima_aceptable' => ['nullable', 'numeric', 'between:-30,70', 'gt:temp_minima_aceptable'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'registrado_en' => ['nullable', 'date'],
        ]);

        $min = $validated['temp_minima_aceptable'] ?? null;
        $max = $validated['temp_maxima_aceptable'] ?? null;
        $temp = (float) $validated['temperatura'];

        $dentroRango = $min === null && $max === null
            ? true
            : ($min === null || $temp >= $min) && ($max === null || $temp <= $max);

        $registro = RegistroTemperatura::create([
            'sucursal_id' => $request->user()->sucursal_id,
            'tipo_registro' => $validated['tipo_registro'] ?? 'manual',
            'ubicacion' => $validated['ubicacion'] ?? null,
            'dispositivo' => $validated['dispositivo'] ?? null,
            'temperatura' => $temp,
            'humedad' => isset($validated['humedad']) ? (float) $validated['humedad'] : null,
            'temp_minima_aceptable' => $min,
            'temp_maxima_aceptable' => $max,
            'dentro_rango' => $dentroRango,
            'observaciones' => $validated['observaciones'] ?? null,
            'usuario_id' => $request->user()->id,
            'registrado_en' => $validated['registrado_en'] ?? now(),
        ]);

        $status = $dentroRango ? Response::HTTP_CREATED : Response::HTTP_CREATED;

        return response()->json([
            'success' => true,
            'message' => $dentroRango
                ? 'Registro de temperatura guardado'
                : 'Registro guardado: temperatura fuera del rango aceptable',
            'data' => $this->formatear($registro),
        ], $status);
    }

    /**
     * Mostrar un registro.
     */
    public function show(RegistroTemperatura $registroTemperatura): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->formatear($registroTemperatura->load(['sucursal', 'usuario'])),
        ], Response::HTTP_OK);
    }

    /**
     * Resumen de alertas: registros fuera de rango recientes.
     */
    public function alertas(Request $request): JsonResponse
    {
        $sucursalId = $request->user()->sucursal_id;

        $recientes = RegistroTemperatura::query()
            ->where('dentro_rango', false)
            ->where('registrado_en', '>=', now()->subDays(30))
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->orderByDesc('registrado_en')
            ->limit(20)
            ->get();

        $totalFuera = RegistroTemperatura::query()
            ->where('dentro_rango', false)
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->count();

        $ultimo = RegistroTemperatura::query()
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->orderByDesc('registrado_en')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'total_fuera_rango' => $totalFuera,
                'ultimo_registro' => $ultimo ? $this->formatear($ultimo) : null,
                'recientes' => $recientes->map(fn ($r) => $this->formatear($r))->values(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatear(RegistroTemperatura $r): array
    {
        return [
            'id' => $r->id,
            'tipo_registro' => $r->tipo_registro,
            'ubicacion' => $r->ubicacion,
            'dispositivo' => $r->dispositivo,
            'temperatura' => (float) $r->temperatura,
            'humedad' => $r->humedad !== null ? (float) $r->humedad : null,
            'temp_minima_aceptable' => $r->temp_minima_aceptable !== null ? (float) $r->temp_minima_aceptable : null,
            'temp_maxima_aceptable' => $r->temp_maxima_aceptable !== null ? (float) $r->temp_maxima_aceptable : null,
            'dentro_rango' => $r->dentro_rango,
            'observaciones' => $r->observaciones,
            'registrado_en' => $r->registrado_en,
            'sucursal' => $r->sucursal ? [
                'id' => $r->sucursal->id,
                'nombre' => $r->sucursal->nombre,
            ] : null,
            'usuario' => $r->usuario ? ['id' => $r->usuario->id, 'nombre' => $r->usuario->nombre] : null,
            'created_at' => $r->created_at,
        ];
    }
}