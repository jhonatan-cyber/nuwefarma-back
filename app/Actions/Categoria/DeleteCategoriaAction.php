<?php

declare(strict_types=1);

namespace App\Actions\Categoria;

use App\Models\Categoria;
use App\Exceptions\ApiException;

class DeleteCategoriaAction
{
    /**
     * Delete a category with business logic validation.
     *
     * @param Categoria $categoria
     * @return bool
     * @throws ApiException
     */
    public function execute(Categoria $categoria): bool
    {
        $this->validateDeletion($categoria);

        return $categoria->delete();
    }

    /**
     * Validate if category can be deleted.
     *
     * @param Categoria $categoria
     * @throws ApiException
     */
    private function validateDeletion(Categoria $categoria): void
    {
        if ($categoria->productos()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar la categoría',
                ['productos' => ['La categoría tiene productos asociados']]
            );
        }
    }
}
