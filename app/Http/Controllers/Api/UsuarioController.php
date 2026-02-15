<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Usuario\BulkUpdateUsuariosAction;
use App\Actions\Usuario\CreateUsuarioAction;
use App\Actions\Usuario\DeleteUsuarioAction;
use App\Actions\Usuario\ListUsuariosAction;
use App\Actions\Usuario\UpdateUsuarioAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\UserResource;
use App\Http\Resources\Usuario\UsuarioCollection;
use App\Http\Resources\Usuario\UsuarioResource;
use App\Models\Usuario;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function __construct(
        private CreateUsuarioAction $createUsuarioAction,
        private UpdateUsuarioAction $updateUsuarioAction,
        private DeleteUsuarioAction $deleteUsuarioAction,
        private ListUsuariosAction $listUsuariosAction,
        private BulkUpdateUsuariosAction $bulkUpdateUsuariosAction
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'search', 'estado', 'rol_id', 'sucursal_id',
            'sort', 'direction', 'per_page',
        ]);

        $usuarios = $this->listUsuariosAction->execute($filters);

        return $this->success(new UsuarioCollection($usuarios));
    }

    public function store(Request $request)
    {
        $usuario = $this->createUsuarioAction->execute($request->all());

        return $this->created(
            new UsuarioResource($usuario->load(['rol', 'sucursal'])),
            'Usuario creado exitosamente'
        );
    }

    public function show(Usuario $usuario)
    {
        return $this->success(
            new UsuarioResource($usuario->load(['rol', 'sucursal']))
        );
    }

    public function update(Request $request, Usuario $usuario)
    {
        $updatedUsuario = $this->updateUsuarioAction->execute($usuario, $request->all());

        return $this->success(
            new UsuarioResource($updatedUsuario),
            'Usuario actualizado exitosamente'
        );
    }

    public function destroy(Usuario $usuario)
    {
        $this->deleteUsuarioAction->execute($usuario);

        return $this->noContent('Usuario eliminado exitosamente');
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:usuarios,id'],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);

        $updatedCount = $this->bulkUpdateUsuariosAction->execute($validated['ids'], $validated['estado']);

        return $this->success([
            'updated_count' => $updatedCount,
        ], 'Usuarios actualizados exitosamente');
    }

    public function profile(Request $request)
    {
        return $this->success(
            new UserResource($request->user()->load(['rol', 'sucursal']))
        );
    }
}
