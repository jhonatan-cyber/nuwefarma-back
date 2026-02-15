<?php

namespace App\Policies;

use App\Models\Caja;
use App\Models\Usuario;
use Illuminate\Auth\Access\HandlesAuthorization;

class CajaPolicy
{
    use HandlesAuthorization;

    public function viewAny(Usuario $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Cajero']);
    }

    public function view(Usuario $user, Caja $caja): bool
    {
        if ($user->hasAnyRole(['Administrador', 'Gerente'])) {
            return true;
        }

        return $caja->usuario_id === $user->id;
    }

    public function create(Usuario $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente']);
    }

    public function update(Usuario $user, Caja $caja): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente']);
    }

    public function delete(Usuario $user, Caja $caja): bool
    {
        return $user->hasRole('Administrador');
    }

    public function abrir(Usuario $user, Caja $caja): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Cajero']);
    }

    public function cerrar(Usuario $user, Caja $caja): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Cajero']) && $caja->usuario_id === $user->id;
    }
}
