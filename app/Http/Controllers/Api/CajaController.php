<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Caja\AbrirCajaAction;
use App\Actions\Caja\CerrarCajaAction;
use App\Actions\Caja\CreateCajaAction;
use App\Actions\Caja\DeleteCajaAction;
use App\Actions\Caja\ListCajasAction;
use App\Actions\Caja\UpdateCajaAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Caja\ArqueoCajaCollection;
use App\Http\Resources\Caja\ArqueoCajaResource;
use App\Http\Resources\Caja\CajaCollection;
use App\Http\Resources\Caja\CajaResource;
use App\Http\Resources\Caja\MovimientoCajaResource;
use App\Models\ArqueoCaja;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Services\ArqueoCajaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CajaController extends Controller
{
    // Simplificar sin DTOs y Actions complejas por ahora
    // public function __construct(
    //     private CreateCajaAction $createCajaAction,
    //     private UpdateCajaAction $updateCajaAction,
    //     private DeleteCajaAction $deleteCajaAction,
    //     private ListCajasAction $listCajasAction,
    //     private AbrirCajaAction $abrirCajaAction,
    //     private CerrarCajaAction $cerrarCajaAction
    // ) {}

    /**
     * Display a paginated listing of cash registers with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Caja::with(['sucursal', 'usuario']);

        // Aplicar filtros básicos
        if ($request->search) {
            $query->where('nombre', 'like', "%{$request->search}%")
                ->orWhere('numero_caja', 'like', "%{$request->search}%");
        }

        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        if ($request->sucursal_id) {
            $query->where('sucursal_id', $request->sucursal_id);
        }

        if ($request->usuario_id) {
            $query->where('usuario_id', $request->usuario_id);
        }

        // Filtros específicos
        if ($request->abiertas) {
            $query->where('estado', 'abierta');
        }

        if ($request->saldo_min) {
            $query->where('saldo_actual', '>=', $request->saldo_min);
        }

        if ($request->saldo_max) {
            $query->where('saldo_actual', '<=', $request->saldo_max);
        }

        // Ordenamiento
        $sort = $request->sort ?? 'nombre';
        $direction = $request->direction ?? 'asc';
        $query->orderBy($sort, $direction);

        $perPage = min($request->per_page ?? 15, 100);
        $cajas = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => new CajaCollection($cajas),
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created cash register in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Agregar validación
            $validated = $request->validate([
                'nombre' => ['required', 'string', 'max:255'],
                'numero_caja' => ['required', 'string', 'max:50', 'unique:cajas,numero_caja'],
                'sucursal_id' => ['required', 'exists:sucursals,id'],
                'gerente_id' => ['nullable', 'exists:usuarios,id'],
                'saldo_inicial' => ['required', 'numeric', 'min:0'],
                'descripcion' => ['nullable', 'string', 'max:1000'],
            ]);

            $caja = Caja::create([
                'nombre' => $validated['nombre'],
                'numero_caja' => $validated['numero_caja'],
                'sucursal_id' => $validated['sucursal_id'],
                'usuario_id' => $validated['gerente_id'] ?? null,
                'saldo_inicial' => $validated['saldo_inicial'],
                'saldo_actual' => $validated['saldo_inicial'],
                'descripcion' => $validated['descripcion'] ?? null,
                'estado' => 'cerrada',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Caja creada exitosamente',
                'data' => new CajaResource($caja->load(['sucursal', 'usuario'])),
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación en los datos enviados',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Display the specified cash register with relationships.
     *
     * @param  Caja  $caja
     */
    public function show($id): JsonResponse
    {
        try {
            $caja = Caja::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new CajaResource($caja->load(['sucursal', 'usuario'])),
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Caja no encontrada',
            ], 404);
        }
    }

    /**
     * Update the specified cash register in storage.
     *
     * @param  Caja  $caja
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $caja = Caja::findOrFail($id);

            $validated = $request->validate([
                'nombre' => ['sometimes', 'string', 'max:255'],
                'codigo' => ['sometimes', 'string', 'max:50', 'unique:cajas,codigo,'.$id],
                'sucursal_id' => ['sometimes', 'exists:sucursals,id'],
                'gerente_id' => ['sometimes', 'exists:usuarios,id'],
                'saldo_inicial' => ['sometimes', 'numeric', 'min:0'],
                'descripcion' => ['nullable', 'string', 'max:1000'],
            ]);

            $caja->update([
                'nombre' => $validated['nombre'] ?? $caja->nombre,
                'codigo' => $validated['codigo'] ?? $caja->codigo,
                'sucursal_id' => $validated['sucursal_id'] ?? $caja->sucursal_id,
                'gerente_id' => $validated['gerente_id'] ?? $caja->gerente_id,
                'saldo_inicial' => $validated['saldo_inicial'] ?? $caja->saldo_inicial,
                'descripcion' => $validated['descripcion'] ?? $caja->descripcion,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Caja actualizada exitosamente',
                'data' => new CajaResource($caja->load(['sucursal', 'usuario'])),
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Caja no encontrada',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación en los datos enviados',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Remove the specified cash register from storage.
     *
     * @param  Caja  $caja
     */
    public function destroy($id): JsonResponse
    {
        try {
            $caja = Caja::findOrFail($id);
            $caja->delete();

            return response()->json([
                'success' => true,
                'message' => 'Caja eliminada exitosamente',
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Caja no encontrada',
            ], 404);
        }
    }

    /**
     * Open a cash register.
     *
     * @param  Caja  $caja
     */
    public function abrir(Request $request, $id): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $id): JsonResponse {
                $caja = Caja::query()->lockForUpdate()->findOrFail($id);

                if ($caja->estado === 'abierta') {
                    return response()->json([
                        'success' => false,
                        'message' => 'La caja ya está abierta',
                    ], 400);
                }

                $validated = $request->validate([
                    'monto_apertura' => ['required', 'numeric', 'min:0'],
                    'observaciones' => ['nullable', 'string', 'max:1000'],
                ]);

                $caja->update([
                    'estado' => 'abierta',
                    'saldo_actual' => $caja->saldo_inicial + $validated['monto_apertura'],
                    'fecha_apertura' => now(),
                    'observaciones' => $validated['observaciones'] ?? $caja->observaciones,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Caja abierta exitosamente',
                    'data' => new CajaResource($caja->load(['sucursal', 'usuario'])),
                ], Response::HTTP_OK);
            }, 3);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Caja no encontrada',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación en los datos enviados',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Close a cash register.
     *
     * @param  Caja  $caja
     */
    public function cerrar(Request $request, $id): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $id): JsonResponse {
                $caja = Caja::query()->lockForUpdate()->findOrFail($id);

                if ($caja->estado === 'cerrada') {
                    return response()->json([
                        'success' => false,
                        'message' => 'La caja ya está cerrada',
                    ], 400);
                }

                $validated = $request->validate([
                    'monto_final' => ['required', 'numeric', 'min:0'],
                    'observaciones' => ['nullable', 'string', 'max:1000'],
                ]);

                $caja->update([
                    'estado' => 'cerrada',
                    'saldo_final' => $validated['monto_final'],
                    'fecha_cierre' => now(),
                    'observaciones' => $validated['observaciones'] ?? $caja->observaciones,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Caja cerrada exitosamente',
                    'data' => new CajaResource($caja->load(['sucursal', 'usuario'])),
                ], Response::HTTP_OK);
            }, 3);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Caja no encontrada',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación en los datos enviados',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Get open cash registers.
     */
    public function abiertas(Request $request): JsonResponse
    {
        $query = Caja::with(['sucursal', 'gerente'])
            ->where('estado', 'abierta');

        $perPage = min($request->per_page ?? 15, 100);
        $cajas = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => new CajaCollection($cajas),
        ], Response::HTTP_OK);
    }

    /**
     * Get closed cash registers.
     */
    public function cerradas(Request $request): JsonResponse
    {
        $query = Caja::with(['sucursal', 'gerente'])
            ->where('estado', 'cerrada');

        $perPage = min($request->per_page ?? 15, 100);
        $cajas = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => new CajaCollection($cajas),
        ], Response::HTTP_OK);
    }

    /**
     * Realizar un arqueo de caja con conteo físico por denominación.
     */
    public function arqueo(Request $request, $id): JsonResponse
    {
        $caja = Caja::findOrFail($id);

        $arqueo = app(ArqueoCajaService::class)->realizar($caja, $request->all());

        return $this->success(new ArqueoCajaResource($arqueo), 'Arqueo de caja registrado exitosamente');
    }

    /**
     * Conciliar un arqueo: ajusta el saldo de la caja por la diferencia detectada.
     */
    public function conciliar(Request $request, $id): JsonResponse
    {
        $arqueo = ArqueoCaja::findOrFail($id);

        $conciliado = app(ArqueoCajaService::class)->conciliar($arqueo, $request->all());

        return $this->success(new ArqueoCajaResource($conciliado), 'Arqueo conciliado exitosamente');
    }

    /**
     * Listar los arqueos realizados de una caja.
     */
    public function arqueos(Request $request, $id): JsonResponse
    {
        $caja = Caja::findOrFail($id);

        $query = ArqueoCaja::with(['caja', 'usuario'])
            ->where('caja_id', $caja->id);

        if ($request->estado && in_array($request->estado, ['realizado', 'conciliado'], true)) {
            $query->where('estado', $request->estado);
        }

        $query->orderByDesc('created_at');

        $perPage = min($request->per_page ?? 15, 100);

        return $this->success(new ArqueoCajaCollection($query->paginate($perPage)));
    }

    /**
     * Listar los movimientos (libro diario) de una caja.
     */
    public function movimientos(Request $request, $id): JsonResponse
    {
        $caja = Caja::findOrFail($id);

        $query = MovimientoCaja::with(['caja', 'usuario'])
            ->where('caja_id', $caja->id);

        if ($request->tipo && in_array($request->tipo, ['ingreso', 'egreso'], true)) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->origen) {
            $query->where('origen', $request->origen);
        }

        if ($request->fecha_inicio) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }

        if ($request->fecha_fin) {
            $query->whereDate('created_at', '<=', $request->fecha_fin);
        }

        $query->orderByDesc('created_at');

        $perPage = min($request->per_page ?? 15, 100);

        $movimientos = $query->paginate($perPage);

        return $this->success([
            'data' => MovimientoCajaResource::collection($movimientos),
            'meta' => [
                'total' => $movimientos->total(),
                'count' => $movimientos->count(),
                'per_page' => $movimientos->perPage(),
                'current_page' => $movimientos->currentPage(),
                'last_page' => $movimientos->lastPage(),
            ],
            'resumen' => [
                'total_ingresos' => round((float) $caja->total_ingresos, 2),
                'total_egresos' => round((float) $caja->total_egresos, 2),
                'saldo_actual' => round((float) $caja->saldo_actual, 2),
            ],
        ], 'Movimientos de caja');
    }
}
