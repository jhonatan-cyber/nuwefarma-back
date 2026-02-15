<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductoPolicy
{
    use HandlesAuthorization;

    public function viewAny(Usuario $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Vendedor', 'Almacenero']);
    }

    public function view(Usuario $user, Producto $producto): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Vendedor', 'Almacenero']);
    }

    public function create(Usuario $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Almacenero']);
    }

    public function update(Usuario $user, Producto $producto): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Almacenero']);
    }

    public function delete(Usuario $user, Producto $producto): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente']);
    }

    public function restore(Usuario $user, Producto $producto): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente']);
    }

    public function forceDelete(Usuario $user, Producto $producto): bool
    {
        return $user->hasRole('Administrador');
    }

    public function manageStock(Usuario $user, Producto $producto): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente', 'Almacenero']);
    }

    public function updatePrice(Usuario $user, Producto $producto): bool
    {
        return $user->hasAnyRole(['Administrador', 'Gerente']);
    }
}
