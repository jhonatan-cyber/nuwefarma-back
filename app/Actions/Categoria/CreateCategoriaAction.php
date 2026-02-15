<?php

declare(strict_types=1);

namespace App\Actions\Categoria;

use App\DTOs\Categoria\CreateCategoriaDTO;
use App\Models\Categoria;

class CreateCategoriaAction
{
    /**
     * Create a new category.
     */
    public function execute(CreateCategoriaDTO $data): Categoria
    {
        return Categoria::create([
            'nombre' => $data->nombre,
            'descripcion' => $data->descripcion,
            'estado' => $data->estado,
        ]);
    }
}
