<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClientePolicy
{
    use HandlesAuthorization;

    public function viewAny(Usuario $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Vendedor']);
    }

    public function view(Usuario $user, Cliente $cliente): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Vendedor']);
    }

    public function create(Usuario $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Vendedor']);
    }

    public function update(Usuario $user, Cliente $cliente): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Vendedor']);
    }

    public function delete(Usuario $user, Cliente $cliente): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente']);
    }
}
