<?php

namespace App\Policies;

use App\Models\Categoria;
use App\Models\Usuario;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoriaPolicy
{
    use HandlesAuthorization;

    public function viewAny(Usuario $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Vendedor', 'Almacenero']);
    }

    public function view(Usuario $user, Categoria $categoria): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Vendedor', 'Almacenero']);
    }

    public function create(Usuario $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente']);
    }

    public function update(Usuario $user, Categoria $categoria): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente']);
    }

    public function delete(Usuario $user, Categoria $categoria): bool
    {
        return $user->hasRole('Administrador');
    }
}
