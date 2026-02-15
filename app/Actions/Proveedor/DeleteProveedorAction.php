<?php

declare(strict_types=1);

namespace App\Actions\Proveedor;

use App\Exceptions\ApiException;
use App\Models\Proveedor;

class DeleteProveedorAction
{
    /**
     * Delete a provider with business logic validation.
     *
     * @throws ApiException
     */
    public function execute(Proveedor $proveedor): bool
    {
        $this->validateDeletion($proveedor);

        return $proveedor->delete();
    }

    /**
     * Validate if provider can be deleted.
     *
     * @throws ApiException
     */
    private function validateDeletion(Proveedor $proveedor): void
    {
        // Check if provider has related records
        if ($proveedor->productos()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar el proveedor',
                ['productos' => ['El proveedor tiene productos asociados']]
            );
        }

        if ($proveedor->compras()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar el proveedor',
                ['compras' => ['El proveedor tiene compras asociadas']]
            );
        }
    }
}
