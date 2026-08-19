<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Cliente\BulkUpdateClientesAction;
use App\Actions\Cliente\CreateClienteAction;
use App\Actions\Cliente\DeleteClienteAction;
use App\Actions\Cliente\ListClientesAction;
use App\Actions\Cliente\UpdateClienteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\StoreClienteRequest;
use App\Http\Requests\Cliente\UpdateClienteRequest;
use App\Http\Resources\Cliente\ClienteCollection;
use App\Http\Resources\Cliente\ClienteResource;
use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ClienteController extends Controller
{
    public function __construct(
        private CreateClienteAction $createClienteAction,
        private UpdateClienteAction $updateClienteAction,
        private DeleteClienteAction $deleteClienteAction,
        private ListClientesAction $listClientesAction,
        private BulkUpdateClientesAction $bulkUpdateClientesAction
    ) {}

    /**
     * Display a paginated listing of clients with filtering.
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'search', 'estado', 'ci', 'nit', 'con_deuda',
            'sort', 'direction', 'per_page',
        ]);

        $clientes = $this->listClientesAction->execute($filters);

        return $this->success(new ClienteCollection($clientes));
    }

    /**
     * Store a newly created client in storage.
     */
    public function store(StoreClienteRequest $request)
    {
        $cliente = $this->createClienteAction->execute($request->validated());

        return $this->created(
            new ClienteResource($cliente),
            'Cliente creado exitosamente'
        );
    }

    /**
     * Display the specified client with relationships.
     */
    public function show(Cliente $cliente)
    {
        return $this->success(
            new ClienteResource($cliente->loadCount('ventas'))
        );
    }

    /**
     * Update the specified client in storage.
     */
    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        $updatedCliente = $this->updateClienteAction->execute($cliente, $request->validated());

        return $this->success(
            new ClienteResource($updatedCliente->loadCount('ventas')),
            'Cliente actualizado exitosamente'
        );
    }

    /**
     * Remove the specified client from storage.
     */
    public function destroy(Cliente $cliente)
    {
        $this->deleteClienteAction->execute($cliente);

        return $this->noContent('Cliente eliminado exitosamente');
    }

    /**
     * Bulk update clients status.
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:clientes,id'],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);

        $updatedCount = $this->bulkUpdateClientesAction->execute($validated['ids'], $validated['estado']);

        return $this->success([
            'updated_count' => $updatedCount,
        ], 'Clientes actualizados exitosamente');
    }

    /**
     * Get clients with outstanding debt.
     */
    public function conDeuda(Request $request)
    {
        $filters = array_merge($request->only(['per_page']), ['con_deuda' => true]);

        $clientes = $this->listClientesAction->execute($filters);

        return $this->success(new ClienteCollection($clientes));
    }

    /**
     * Toggle client status between active and inactive.
     */
    public function toggleEstado(Cliente $cliente)
    {
        $cliente->estado = $cliente->estado === 'activo' ? 'inactivo' : 'activo';
        $cliente->save();

        return $this->success(
            new ClienteResource($cliente->loadCount('ventas')),
            'Estado del cliente actualizado exitosamente'
        );
    }

    /**
     * Get client statistics overview.
     */
    public function statsOverview()
    {
        $total = Cliente::count();
        $activos = Cliente::where('estado', 'activo')->count();
        $inactivos = Cliente::where('estado', 'inactivo')->count();
        $conDeuda = 0;

        if (Schema::hasColumn('ventas', 'saldo_pendiente')) {
            $conDeuda = Cliente::whereHas('ventas', function ($query) {
                $query->where('saldo_pendiente', '>', 0);
            })->count();
        }

        return $this->success([
            'total' => $total,
            'activos' => $activos,
            'inactivos' => $inactivos,
            'con_deuda' => $conDeuda,
            'sin_deuda' => $total - $conDeuda,
        ]);
    }

    /**
     * Estado de cuenta del cliente: ventas por cobrar, pagos realizados y saldo.
     */
    public function estadoCuenta(Cliente $cliente)
    {
        $ventas = Venta::with(['pagos'])
            ->where('cliente_id', $cliente->id)
            ->whereNotIn('estado', ['cancelada', 'devuelta'])
            ->orderByDesc('fecha_venta')
            ->get();

        $porCobrar = $ventas
            ->filter(fn (Venta $venta) => (float) $venta->saldo_pendiente > 0)
            ->values();

        $pagos = $ventas
            ->flatMap(fn (Venta $venta) => $venta->pagos)
            ->sortByDesc('fecha_pago')
            ->values();

        return $this->success([
            'cliente' => [
                'id' => $cliente->id,
                'nombre' => trim($cliente->nombre.' '.($cliente->apellidos ?? '')),
                'ci' => $cliente->ci,
                'email' => $cliente->email,
                'telefono' => $cliente->telefono,
            ],
            'resumen' => [
                'total_vendido' => round((float) $ventas->where('estado', 'completada')->sum('total'), 2),
                'total_pagado' => round((float) $ventas->sum('pagado'), 2),
                'saldo_pendiente' => round((float) $ventas->sum('saldo_pendiente'), 2),
                'cantidad_cuentas_abiertas' => $porCobrar->count(),
            ],
            'cuentas_por_cobrar' => $porCobrar->map(fn (Venta $venta) => [
                'id' => $venta->id,
                'documento_numero' => $venta->numero_venta,
                'fecha' => $venta->fecha_venta,
                'fecha_vencimiento' => $venta->fecha_vencimiento,
                'total' => $venta->total,
                'pagado' => $venta->pagado,
                'saldo_pendiente' => $venta->saldo_pendiente,
                'estado' => $venta->estado,
            ]),
            'pagos' => $pagos->map(fn ($pago) => [
                'id' => $pago->id,
                'numero_pago' => $pago->numero_pago,
                'documento_numero' => $pago->documento_numero,
                'fecha_pago' => $pago->fecha_pago,
                'monto' => $pago->monto,
                'metodo_pago' => $pago->metodo_pago,
                'tipo_pago' => $pago->tipo_pago,
            ]),
        ], 'Estado de cuenta del cliente');
    }
}
