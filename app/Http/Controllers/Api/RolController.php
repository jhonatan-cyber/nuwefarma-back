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
        try {
            \Log::info('Creating rol with data:', $request->validated());
            
            $rol = $this->createRolAction->execute($request->validated());
            
            \Log::info('Rol created successfully:', ['id' => $rol->id]);

            return $this->created(
                new RolResource($rol),
                'Rol creado exitosamente'
            );
        } catch (\Exception $e) {
            \Log::error('Error creating rol:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el rol: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Rol $role)
    {
        return $this->success(
            new RolResource($role->loadCount('usuarios'))
        );
    }

    public function update(UpdateRolRequest $request, Rol $role)
    {
        try {
            \Log::info('Updating rol:', [
                'rol_id' => $role->id,
                'data' => $request->validated()
            ]);
            
            $updatedRol = $this->updateRolAction->execute($role, $request->validated());
            
            \Log::info('Rol updated successfully:', ['id' => $updatedRol->id]);

            return $this->success(
                new RolResource($updatedRol->loadCount('usuarios')),
                'Rol actualizado exitosamente'
            );
        } catch (\Exception $e) {
            \Log::error('Error updating rol:', [
                'rol_id' => $role->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el rol: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Rol $role)
    {
        $this->deleteRolAction->execute($role);

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

    public function toggleEstado(Rol $role)
    {
        try {
            $nuevoEstado = $role->estado === 'activo' ? 'inactivo' : 'activo';
            
            // Contar usuarios afectados
            $usuariosAfectados = $role->usuarios()->count();
            
            // Actualizar el estado
            $role->update(['estado' => $nuevoEstado]);
            
            return $this->success([
                'id' => $role->id,
                'estado' => $nuevoEstado,
                'usuarios_afectados' => $usuariosAfectados,
            ], "Rol {$nuevoEstado} exitosamente");
        } catch (\Exception $e) {
            \Log::error('Error toggling rol estado:', [
                'rol_id' => $role->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del rol: ' . $e->getMessage()
            ], 500);
        }
    }
}
