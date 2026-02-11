<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sucursal\SucursalCollection;
use App\Http\Resources\Sucursal\SucursalResource;
use App\Models\Sucursal;
use App\Actions\Sucursal\CreateSucursalAction;
use App\Actions\Sucursal\UpdateSucursalAction;
use App\Actions\Sucursal\DeleteSucursalAction;
use App\Actions\Sucursal\ListSucursalesAction;
use App\Actions\Sucursal\BulkUpdateSucursalesAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SucursalController extends Controller
{
    public function __construct(
        private CreateSucursalAction $createSucursalAction,
        private UpdateSucursalAction $updateSucursalAction,
        private DeleteSucursalAction $deleteSucursalAction,
        private ListSucursalesAction $listSucursalesAction,
        private BulkUpdateSucursalesAction $bulkUpdateSucursalesAction
    ) {}

    /**
     * Display a paginated listing of branches with filtering.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'estado', 'ciudad', 'departamento', 'pais',
            'gerente_id', 'capacidad_min', 'capacidad_max',
            'sort', 'direction', 'per_page'
        ]);
        
        $sucursales = $this->listSucursalesAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new SucursalCollection($sucursales)
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created branch in storage.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $sucursal = $this->createSucursalAction->execute($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Sucursal creada exitosamente',
            'data' => new SucursalResource($sucursal->load('gerente'))
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified branch with relationships.
     * 
     * @param Sucursal $sucursal
     * @return JsonResponse
     */
    public function show(Sucursal $sucursal): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new SucursalResource($sucursal->loadCount(['usuarios', 'cajas']))
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified branch in storage.
     * 
     * @param Request $request
     * @param Sucursal $sucursal
     * @return JsonResponse
     */
    public function update(Request $request, Sucursal $sucursal): JsonResponse
    {
        $updatedSucursal = $this->updateSucursalAction->execute($sucursal, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Sucursal actualizada exitosamente',
            'data' => new SucursalResource($updatedSucursal->loadCount(['usuarios', 'cajas']))
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified branch from storage.
     * 
     * @param Sucursal $sucursal
     * @return JsonResponse
     */
    public function destroy(Sucursal $sucursal): JsonResponse
    {
        $this->deleteSucursalAction->execute($sucursal);

        return response()->json([
            'success' => true,
            'message' => 'Sucursal eliminada exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Bulk update branches status.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:sucursals,id'],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);

        $updatedCount = $this->bulkUpdateSucursalesAction->execute($validated['ids'], $validated['estado']);

        return response()->json([
            'success' => true,
            'message' => 'Sucursales actualizadas exitosamente',
            'data' => [
                'updated_count' => $updatedCount
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Get active branches.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function activas(Request $request): JsonResponse
    {
        $filters = array_merge($request->only(['per_page']), ['estado' => 'activo']);
        
        $sucursales = $this->listSucursalesAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new SucursalCollection($sucursales)
        ], Response::HTTP_OK);
    }
}
