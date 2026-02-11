<?php

declare(strict_types=1);

namespace App\Actions\Sucursal;

use App\Models\Sucursal;
use App\Exceptions\ApiException;

class DeleteSucursalAction
{
    /**
     * Delete a branch with business logic validation.
     *
     * @param Sucursal $sucursal
     * @return bool
     * @throws ApiException
     */
    public function execute(Sucursal $sucursal): bool
    {
        $this->validateDeletion($sucursal);

        return $sucursal->delete();
    }

    /**
     * Validate if branch can be deleted.
     *
     * @param Sucursal $sucursal
     * @throws ApiException
     */
    private function validateDeletion(Sucursal $sucursal): void
    {
        // Check if branch has related records
        if ($sucursal->usuarios()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar la sucursal',
                ['usuarios' => ['La sucursal tiene usuarios asignados']]
            );
        }

        if ($sucursal->cajas()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar la sucursal',
                ['cajas' => ['La sucursal tiene cajas asociadas']]
            );
        }

        if ($sucursal->ventas()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar la sucursal',
                ['ventas' => ['La sucursal tiene ventas asociadas']]
            );
        }

        if ($sucursal->compras()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar la sucursal',
                ['compras' => ['La sucursal tiene compras asociadas']]
            );
        }

        if ($sucursal->productos()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar la sucursal',
                ['productos' => ['La sucursal tiene productos asociados']]
            );
        }
    }
}
