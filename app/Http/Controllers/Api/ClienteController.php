<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Cliente\ClienteCollection;
use App\Http\Resources\Cliente\ClienteResource;
use App\Models\Cliente;
use App\Actions\Cliente\CreateClienteAction;
use App\Actions\Cliente\UpdateClienteAction;
use App\Actions\Cliente\DeleteClienteAction;
use App\Actions\Cliente\ListClientesAction;
use App\Actions\Cliente\BulkUpdateClientesAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'estado', 'ci', 'nit', 'con_deuda',
            'sort', 'direction', 'per_page'
        ]);
        
        $clientes = $this->listClientesAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new ClienteCollection($clientes)
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created client in storage.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $cliente = $this->createClienteAction->execute($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Cliente creado exitosamente',
            'data' => new ClienteResource($cliente)
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified client with relationships.
     * 
     * @param Cliente $cliente
     * @return JsonResponse
     */
    public function show(Cliente $cliente): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new ClienteResource($cliente->loadCount('ventas'))
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified client in storage.
     * 
     * @param Request $request
     * @param Cliente $cliente
     * @return JsonResponse
     */
    public function update(Request $request, Cliente $cliente): JsonResponse
    {
        $updatedCliente = $this->updateClienteAction->execute($cliente, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Cliente actualizado exitosamente',
            'data' => new ClienteResource($updatedCliente->loadCount('ventas'))
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified client from storage.
     * 
     * @param Cliente $cliente
     * @return JsonResponse
     */
    public function destroy(Cliente $cliente): JsonResponse
    {
        $this->deleteClienteAction->execute($cliente);

        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Bulk update clients status.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:clientes,id'],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);

        $updatedCount = $this->bulkUpdateClientesAction->execute($validated['ids'], $validated['estado']);

        return response()->json([
            'success' => true,
            'message' => 'Clientes actualizados exitosamente',
            'data' => [
                'updated_count' => $updatedCount
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Get clients with outstanding debt.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function conDeuda(Request $request): JsonResponse
    {
        $filters = array_merge($request->only(['per_page']), ['con_deuda' => true]);
        
        $clientes = $this->listClientesAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new ClienteCollection($clientes)
        ], Response::HTTP_OK);
    }
}
