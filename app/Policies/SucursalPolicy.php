<?php

namespace App\Policies;

use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Auth\Access\HandlesAuthorization;

class SucursalPolicy
{
    use HandlesAuthorization;

    public function viewAny(Usuario $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Vendedor', 'Almacenero']);
    }

    public function view(Usuario $user, Sucursal $sucursal): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente']);
    }

    public function create(Usuario $user): bool
    {
        return $user->hasRole('Administrador');
    }

    public function update(Usuario $user, Sucursal $sucursal): bool
    {
        return $user->hasRole('Administrador');
    }

    public function delete(Usuario $user, Sucursal $sucursal): bool
    {
        return $user->hasRole('Administrador');
    }
}
