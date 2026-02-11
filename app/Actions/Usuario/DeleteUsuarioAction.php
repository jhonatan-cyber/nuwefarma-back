<?php

declare(strict_types=1);

namespace App\Actions\Usuario;

use App\Models\Usuario;
use App\Exceptions\ApiException;

class DeleteUsuarioAction
{
    /**
     * Delete a user with business logic validation.
     *
     * @param Usuario $usuario
     * @return bool
     * @throws ApiException
     */
    public function execute(Usuario $usuario): bool
    {
        $this->validateDeletion($usuario);

        return $usuario->delete();
    }

    /**
     * Validate if user can be deleted.
     *
     * @param Usuario $usuario
     * @throws ApiException
     */
    private function validateDeletion(Usuario $usuario): void
    {
        // Check if user has related records
        if ($usuario->ventas()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar el usuario',
                ['ventas' => ['El usuario tiene ventas asociadas']]
            );
        }

        if ($usuario->compras()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar el usuario',
                ['compras' => ['El usuario tiene compras asociadas']]
            );
        }

        // Check if user is a manager of any branch
        if ($usuario->sucursalesGerenciadas()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar el usuario',
                ['gerente' => ['El usuario es gerente de sucursales']]
            );
        }
    }
}
