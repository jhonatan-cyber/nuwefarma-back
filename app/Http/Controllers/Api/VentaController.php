<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Venta\CompleteVentaAction;
use App\Actions\Venta\CreateVentaAction;
use App\Actions\Venta\DeleteVentaAction;
use App\Actions\Venta\ListVentasAction;
use App\Actions\Venta\UpdateVentaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Venta\StoreVentaRequest;
use App\Http\Requests\Venta\UpdateVentaRequest;
use App\Http\Resources\Venta\VentaCollection;
use App\Http\Resources\Venta\VentaResource;
use App\Models\Venta;
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
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'search', 'estado', 'tipo_pago', 'metodo_pago',
            'cliente_id', 'usuario_id', 'caja_id',
            'fecha_inicio', 'fecha_fin', 'total_min', 'total_max',
            'con_saldo', 'sort', 'direction', 'per_page',
        ]);

        $ventas = $this->listVentasAction->execute($filters);

        return $this->success(new VentaCollection($ventas));
    }

    /**
     * Store a newly created sale in storage.
     */
    public function store(StoreVentaRequest $request)
    {
        $venta = $this->createVentaAction->execute($request->getValidatedData());

        return $this->created(
            new VentaResource($venta),
            'Venta creada exitosamente'
        );
    }

    /**
     * Display the specified sale with relationships.
     */
    public function show(Venta $venta)
    {
        return $this->success(
            new VentaResource($venta->load([
                'cliente', 'usuario', 'caja', 'ventaProductos.producto',
            ]))
        );
    }

    /**
     * Update the specified sale in storage.
     */
    public function update(UpdateVentaRequest $request, Venta $venta)
    {
        $updatedVenta = $this->updateVentaAction->execute($venta, $request->validated());

        return $this->success(
            new VentaResource($updatedVenta->load([
                'cliente', 'usuario', 'caja', 'ventaProductos.producto',
            ])),
            'Venta actualizada exitosamente'
        );
    }

    /**
     * Remove the specified sale from storage.
     */
    public function destroy(Venta $venta)
    {
        $this->deleteVentaAction->execute($venta);

        return $this->noContent('Venta eliminada exitosamente');
    }

    /**
     * Complete a pending sale.
     */
    public function completar(Request $request, Venta $venta)
    {
        $completedVenta = $this->completeVentaAction->execute($venta, $request->all());

        return $this->success(
            new VentaResource($completedVenta->load([
                'cliente', 'usuario', 'caja',
            ])),
            'Venta completada exitosamente'
        );
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
     */
    public function pendientes(Request $request)
    {
        $filters = array_merge($request->only(['per_page']), ['estado' => 'pendiente']);

        $ventas = $this->listVentasAction->execute($filters);

        return $this->success(new VentaCollection($ventas));
    }

    /**
     * Get sales by date range.
     */
    public function porFecha(Request $request)
    {
        $validated = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $filters = array_merge($validated, $request->only(['per_page']));

        $ventas = $this->listVentasAction->execute($filters);

        return $this->success(new VentaCollection($ventas));
    }
}
