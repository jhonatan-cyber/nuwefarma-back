<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Rol\BulkUpdateRolesAction;
use App\Actions\Rol\CreateRolAction;
use App\Actions\Rol\DeleteRolAction;
use App\Actions\Rol\ListRolesAction;
use App\Actions\Rol\UpdateRolAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rol\StoreRolRequest;
use App\Http\Requests\Rol\UpdateRolRequest;
use App\Http\Resources\Rol\RolCollection;
use App\Http\Resources\Rol\RolResource;
use App\Models\Rol;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function __construct(
        private CreateRolAction $createRolAction,
        private UpdateRolAction $updateRolAction,
        private DeleteRolAction $deleteRolAction,
        private ListRolesAction $listRolesAction,
        private BulkUpdateRolesAction $bulkUpdateRolesAction
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'search', 'estado', 'permiso', 'con_usuarios',
            'sort', 'direction', 'per_page',
        ]);

        $roles = $this->listRolesAction->execute($filters);

        return $this->success(new RolCollection($roles));
    }

    public function store(StoreRolRequest $request)
    {
        $rol = $this->createRolAction->execute($request->validated());

        return $this->created(
            new RolResource($rol),
            'Rol creado exitosamente'
        );
    }

    public function show(Rol $rol)
    {
        return $this->success(
            new RolResource($rol->loadCount('usuarios'))
        );
    }

    public function update(UpdateRolRequest $request, Rol $rol)
    {
        $updatedRol = $this->updateRolAction->execute($rol, $request->validated());

        return $this->success(
            new RolResource($updatedRol->loadCount('usuarios')),
            'Rol actualizado exitosamente'
        );
    }

    public function destroy(Rol $rol)
    {
        $this->deleteRolAction->execute($rol);

        return $this->noContent('Rol eliminado exitosamente');
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:roles,id'],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);

        $updatedCount = $this->bulkUpdateRolesAction->execute($validated['ids'], $validated['estado']);

        return $this->success([
            'updated_count' => $updatedCount,
        ], 'Roles actualizados exitosamente');
    }

    public function conUsuarios(Request $request)
    {
        $filters = array_merge($request->only(['per_page']), ['con_usuarios' => true]);

        $roles = $this->listRolesAction->execute($filters);

        return $this->success(new RolCollection($roles));
    }
}
