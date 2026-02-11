<?php

declare(strict_types=1);

namespace App\Actions\Categoria;

use App\DTOs\Categoria\UpdateCategoriaDTO;
use App\Models\Categoria;

class UpdateCategoriaAction
{
    /**
     * Update an existing category.
     *
     * @param Categoria $categoria
     * @param UpdateCategoriaDTO $data
     * @return Categoria
     */
    public function execute(Categoria $categoria, UpdateCategoriaDTO $data): Categoria
    {
        $updateData = array_filter((array) $data, fn($value) => $value !== null);

        $categoria->update($updateData);

        return $categoria->fresh();
    }
}
