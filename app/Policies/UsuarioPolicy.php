<?php

namespace App\Policies;

use App\Models\Usuario;
use Illuminate\Auth\Access\HandlesAuthorization;

class UsuarioPolicy
{
    use HandlesAuthorization;

    public function viewAny(Usuario $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente']);
    }

    public function view(Usuario $user, Usuario $model): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente']) || $user->id === $model->id;
    }

    public function create(Usuario $user): bool
    {
        return $user->hasRole('Administrador');
    }

    public function update(Usuario $user, Usuario $model): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        if ($user->hasRole('Gerente') && $user->sucursal_id === $model->sucursal_id) {
            return true;
        }

        return $user->id === $model->id;
    }

    public function delete(Usuario $user, Usuario $model): bool
    {
        return $user->hasRole('Administrador') && $user->id !== $model->id;
    }

    public function manageRoles(Usuario $user): bool
    {
        return $user->hasRole('Administrador');
    }
}
