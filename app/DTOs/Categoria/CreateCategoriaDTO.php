<?php

declare(strict_types=1);

namespace App\DTOs\Categoria;

readonly class CreateCategoriaDTO
{
    public function __construct(
        public string $nombre,
        public ?string $descripcion = null,
        public string $estado = 'activo'
    ) {}
}
