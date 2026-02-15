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
use Illuminate\Http\Request;

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
}
