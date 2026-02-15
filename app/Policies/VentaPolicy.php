<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Auth\Access\HandlesAuthorization;

class VentaPolicy
{
    use HandlesAuthorization;

    public function viewAny(Usuario $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Vendedor']);
    }

    public function view(Usuario $user, Venta $venta): bool
    {
        if ($user->hasAnyRole(['Administrador', 'Gerente'])) {
            return true;
        }

        return $venta->usuario_id === $user->id;
    }

    public function create(Usuario $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Vendedor']);
    }

    public function update(Usuario $user, Venta $venta): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente']);
    }

    public function delete(Usuario $user, Venta $venta): bool
    {
        return $user->hasRole('Administrador');
    }

    public function anular(Usuario $user, Venta $venta): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente']);
    }

    public function devolucion(Usuario $user, Venta $venta): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Vendedor']);
    }
}
