<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\UserResource;
use App\Http\Resources\Usuario\UsuarioCollection;
use App\Http\Resources\Usuario\UsuarioResource;
use App\Models\Usuario;
use App\Actions\Usuario\CreateUsuarioAction;
use App\Actions\Usuario\UpdateUsuarioAction;
use App\Actions\Usuario\DeleteUsuarioAction;
use App\Actions\Usuario\ListUsuariosAction;
use App\Actions\Usuario\BulkUpdateUsuariosAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UsuarioController extends Controller
{
    public function __construct(
        private CreateUsuarioAction $createUsuarioAction,
        private UpdateUsuarioAction $updateUsuarioAction,
        private DeleteUsuarioAction $deleteUsuarioAction,
        private ListUsuariosAction $listUsuariosAction,
        private BulkUpdateUsuariosAction $bulkUpdateUsuariosAction
    ) {}

    /**
     * Display a paginated listing of users with filtering.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'estado', 'rol_id', 'sucursal_id', 
            'sort', 'direction', 'per_page'
        ]);
        
        $usuarios = $this->listUsuariosAction->execute($filters);

        return response()->json([
            'success' => true,
            'data' => new UsuarioCollection($usuarios)
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created user in storage.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $usuario = $this->createUsuarioAction->execute($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'data' => new UsuarioResource($usuario->load(['rol', 'sucursal']))
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified user with relationships.
     * 
     * @param Usuario $usuario
     * @return JsonResponse
     */
    public function show(Usuario $usuario): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UsuarioResource($usuario->load(['rol', 'sucursal']))
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified user in storage.
     * 
     * @param Request $request
     * @param Usuario $usuario
     * @return JsonResponse
     */
    public function update(Request $request, Usuario $usuario): JsonResponse
    {
        $updatedUsuario = $this->updateUsuarioAction->execute($usuario, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado exitosamente',
            'data' => new UsuarioResource($updatedUsuario)
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified user from storage.
     * 
     * @param Usuario $usuario
     * @return JsonResponse
     */
    public function destroy(Usuario $usuario): JsonResponse
    {
        $this->deleteUsuarioAction->execute($usuario);

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Bulk update users status.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:usuarios,id'],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);

        $updatedCount = $this->bulkUpdateUsuariosAction->execute($validated['ids'], $validated['estado']);

        return response()->json([
            'success' => true,
            'message' => 'Usuarios actualizados exitosamente',
            'data' => [
                'updated_count' => $updatedCount
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Assign a role to a user.
     * 
     * @param Request $request
     * @param Usuario $usuario
     * @return JsonResponse
     */
    public function assignRole(Request $request, Usuario $usuario): JsonResponse
    {
        $validated = $request->validate([
            'rol_id' => ['required', 'string', 'exists:roles,id'],
        ]);

        $usuario->update(['rol_id' => $validated['rol_id']]);

        return response()->json([
            'success' => true,
            'message' => 'Rol asignado exitosamente',
            'data' => new UsuarioResource($usuario->load(['rol', 'sucursal']))
        ], Response::HTTP_OK);
    }

    /**
     * Get current authenticated user profile.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user()->load(['rol', 'sucursal']))
        ], Response::HTTP_OK);
    }
};
