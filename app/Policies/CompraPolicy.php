<?php

namespace App\Policies;

use App\Models\Compra;
use App\Models\Usuario;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompraPolicy
{
    use HandlesAuthorization;

    public function viewAny(Usuario $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Almacenero']);
    }

    public function view(Usuario $user, Compra $compra): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Almacenero']);
    }

    public function create(Usuario $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Almacenero']);
    }

    public function update(Usuario $user, Compra $compra): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente']);
    }

    public function delete(Usuario $user, Compra $compra): bool
    {
        return $user->hasRole('Administrador');
    }

    public function recibir(Usuario $user, Compra $compra): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Almacenero']);
    }
}
