<?php

declare(strict_types=1);

namespace App\Actions\Rol;

use App\Models\Rol;
use App\Exceptions\ApiException;

class DeleteRolAction
{
    /**
     * Delete a role with business logic validation.
     *
     * @param Rol $rol
     * @return bool
     * @throws ApiException
     */
    public function execute(Rol $rol): bool
    {
        $this->validateDeletion($rol);

        return $rol->delete();
    }

    /**
     * Validate if role can be deleted.
     *
     * @param Rol $rol
     * @throws ApiException
     */
    private function validateDeletion(Rol $rol): void
    {
        // Cannot delete if role has assigned users
        if ($rol->usuarios()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar el rol',
                ['usuarios' => ['El rol tiene usuarios asignados']]
            );
        }

        // Cannot delete if it's a system role
        if (in_array($rol->nombre, ['Administrador', 'Usuario', 'Gerente', 'Vendedor', 'Almacenero'])) {
            throw ApiException::conflict(
                'No se puede eliminar un rol del sistema',
                ['sistema' => ['El rol es un rol del sistema y no puede ser eliminado']]
            );
        }
    }
}
