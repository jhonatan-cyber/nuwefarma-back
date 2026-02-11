<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Proveedor\ProveedorCollection;
use App\Http\Resources\Proveedor\ProveedorResource;
use App\Models\Proveedor;
use App\Actions\Proveedor\CreateProveedorAction;
use App\Actions\Proveedor\UpdateProveedorAction;
use App\Actions\Proveedor\DeleteProveedorAction;
use App\Actions\Proveedor\ListProveedoresAction;
use App\Actions\Proveedor\BulkUpdateProveedoresAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProveedorController extends Controller
{
    public function __construct(
        private CreateProveedorAction $createProveedorAction,
        private UpdateProveedorAction $updateProveedorAction,
        private DeleteProveedorAction $deleteProveedorAction,
        private ListProveedoresAction $listProveedoresAction,
        private BulkUpdateProveedoresAction $bulkUpdateProveedoresAction
    ) {}

    /**
     * Display a paginated listing of providers with filtering.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'estado', 'ruc', 'sort', 'direction', 'per_page'
        ]);
        
        $proveedores = $this->listProveedoresAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new ProveedorCollection($proveedores)
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created provider in storage.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $proveedor = $this->createProveedorAction->execute($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Proveedor creado exitosamente',
            'data' => new ProveedorResource($proveedor)
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified provider with relationships.
     * 
     * @param Proveedor $proveedor
     * @return JsonResponse
     */
    public function show(Proveedor $proveedor): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new ProveedorResource($proveedor->loadCount('productos'))
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified provider in storage.
     * 
     * @param Request $request
     * @param Proveedor $proveedor
     * @return JsonResponse
     */
    public function update(Request $request, Proveedor $proveedor): JsonResponse
    {
        $updatedProveedor = $this->updateProveedorAction->execute($proveedor, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Proveedor actualizado exitosamente',
            'data' => new ProveedorResource($updatedProveedor->loadCount('productos'))
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified provider from storage.
     * 
     * @param Proveedor $proveedor
     * @return JsonResponse
     */
    public function destroy(Proveedor $proveedor): JsonResponse
    {
        $this->deleteProveedorAction->execute($proveedor);

        return response()->json([
            'success' => true,
            'message' => 'Proveedor eliminado exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Bulk update providers status.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:proveedors,id'],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);

        $updatedCount = $this->bulkUpdateProveedoresAction->execute($validated['ids'], $validated['estado']);

        return response()->json([
            'success' => true,
            'message' => 'Proveedores actualizados exitosamente',
            'data' => [
                'updated_count' => $updatedCount
            ]
        ], Response::HTTP_OK);
    }
}
