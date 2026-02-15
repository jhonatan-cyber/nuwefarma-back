<?php

namespace App\Policies;

use App\Models\Proveedor;
use App\Models\Usuario;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProveedorPolicy
{
    use HandlesAuthorization;

    public function viewAny(Usuario $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Almacenero']);
    }

    public function view(Usuario $user, Proveedor $proveedor): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Almacenero']);
    }

    public function create(Usuario $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente']);
    }

    public function update(Usuario $user, Proveedor $proveedor): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente']);
    }

    public function delete(Usuario $user, Proveedor $proveedor): bool
    {
        return $user->hasRole('Administrador');
    }
}
