<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Rol\RolCollection;
use App\Http\Resources\Rol\RolResource;
use App\Models\Rol;
use App\Actions\Rol\CreateRolAction;
use App\Actions\Rol\UpdateRolAction;
use App\Actions\Rol\DeleteRolAction;
use App\Actions\Rol\ListRolesAction;
use App\Actions\Rol\BulkUpdateRolesAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RolController extends Controller
{
    public function __construct(
        private CreateRolAction $createRolAction,
        private UpdateRolAction $updateRolAction,
        private DeleteRolAction $deleteRolAction,
        private ListRolesAction $listRolesAction,
        private BulkUpdateRolesAction $bulkUpdateRolesAction
    ) {}

    /**
     * Display a paginated listing of roles with filtering.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'estado', 'permiso', 'con_usuarios',
            'sort', 'direction', 'per_page'
        ]);
        
        $roles = $this->listRolesAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new RolCollection($roles)
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created role in storage.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $rol = $this->createRolAction->execute($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Rol creado exitosamente',
            'data' => new RolResource($rol)
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified role with relationships.
     * 
     * @param Rol $rol
     * @return JsonResponse
     */
    public function show(Rol $rol): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new RolResource($rol->loadCount('usuarios'))
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified role in storage.
     * 
     * @param Request $request
     * @param Rol $rol
     * @return JsonResponse
     */
    public function update(Request $request, Rol $rol): JsonResponse
    {
        $updatedRol = $this->updateRolAction->execute($rol, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Rol actualizado exitosamente',
            'data' => new RolResource($updatedRol->loadCount('usuarios'))
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified role from storage.
     * 
     * @param Rol $rol
     * @return JsonResponse
     */
    public function destroy(Rol $rol): JsonResponse
    {
        $this->deleteRolAction->execute($rol);

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Bulk update roles status.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:roles,id'],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);

        $updatedCount = $this->bulkUpdateRolesAction->execute($validated['ids'], $validated['estado']);

        return response()->json([
            'success' => true,
            'message' => 'Roles actualizados exitosamente',
            'data' => [
                'updated_count' => $updatedCount
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Get roles with assigned users.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function conUsuarios(Request $request): JsonResponse
    {
        $filters = array_merge($request->only(['per_page']), ['con_usuarios' => true]);
        
        $roles = $this->listRolesAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new RolCollection($roles)
        ], Response::HTTP_OK);
    }
}
