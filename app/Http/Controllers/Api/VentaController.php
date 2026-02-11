<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Venta\VentaCollection;
use App\Http\Resources\Venta\VentaResource;
use App\Models\Venta;
use App\Actions\Venta\CreateVentaAction;
use App\Actions\Venta\UpdateVentaAction;
use App\Actions\Venta\DeleteVentaAction;
use App\Actions\Venta\ListVentasAction;
use App\Actions\Venta\CompleteVentaAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VentaController extends Controller
{
    public function __construct(
        private CreateVentaAction $createVentaAction,
        private UpdateVentaAction $updateVentaAction,
        private DeleteVentaAction $deleteVentaAction,
        private ListVentasAction $listVentasAction,
        private CompleteVentaAction $completeVentaAction
    ) {}

    /**
     * Display a paginated listing of sales with filtering.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'estado', 'tipo_pago', 'metodo_pago',
            'cliente_id', 'usuario_id', 'caja_id',
            'fecha_inicio', 'fecha_fin', 'total_min', 'total_max',
            'con_saldo', 'sort', 'direction', 'per_page'
        ]);
        
        $ventas = $this->listVentasAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new VentaCollection($ventas)
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created sale in storage.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $venta = $this->createVentaAction->execute($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Venta creada exitosamente',
            'data' => new VentaResource($venta)
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified sale with relationships.
     * 
     * @param Venta $venta
     * @return JsonResponse
     */
    public function show(Venta $venta): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new VentaResource($venta->load([
                'cliente', 'usuario', 'caja', 'ventaProductos.producto'
            ]))
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified sale in storage.
     * 
     * @param Request $request
     * @param Venta $venta
     * @return JsonResponse
     */
    public function update(Request $request, Venta $venta): JsonResponse
    {
        $updatedVenta = $this->updateVentaAction->execute($venta, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Venta actualizada exitosamente',
            'data' => new VentaResource($updatedVenta->load([
                'cliente', 'usuario', 'caja', 'ventaProductos.producto'
            ]))
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified sale from storage.
     * 
     * @param Venta $venta
     * @return JsonResponse
     */
    public function destroy(Venta $venta): JsonResponse
    {
        $this->deleteVentaAction->execute($venta);

        return response()->json([
            'success' => true,
            'message' => 'Venta eliminada exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Complete a pending sale.
     * 
     * @param Request $request
     * @param Venta $venta
     * @return JsonResponse
     */
    public function completar(Request $request, Venta $venta): JsonResponse
    {
        $completedVenta = $this->completeVentaAction->execute($venta, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Venta completada exitosamente',
            'data' => new VentaResource($completedVenta->load([
                'cliente', 'usuario', 'caja'
            ]))
        ], Response::HTTP_OK);
    }

    /**
     * Cancel a pending sale.
     * 
     * @param Request $request
     * @param Venta $venta
     * @return JsonResponse
     */
    public function cancelar(Request $request, Venta $venta): JsonResponse
    {
        $request->validate([
            'motivo' => ['required', 'string', 'max:255']
        ]);

        $venta->update([
            'estado' => 'cancelada',
            'motivo_cancelacion' => $request->motivo,
            'fecha_cancelacion' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Venta cancelada exitosamente',
            'data' => new VentaResource($venta->load([
                'cliente', 'usuario', 'caja'
            ]))
        ], Response::HTTP_OK);
    }

    /**
     * Get sales with pending balance.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function pendientes(Request $request): JsonResponse
    {
        $filters = array_merge($request->only(['per_page']), ['estado' => 'pendiente']);
        
        $ventas = $this->listVentasAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new VentaCollection($ventas)
        ], Response::HTTP_OK);
    }

    /**
     * Get sales by date range.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function porFecha(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $filters = array_merge($validated, $request->only(['per_page']));
        
        $ventas = $this->listVentasAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new VentaCollection($ventas)
        ], Response::HTTP_OK);
    }
}
