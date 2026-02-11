<?php

declare(strict_types=1);

namespace App\DTOs\Categoria;

readonly class UpdateCategoriaDTO
{
    public function __construct(
        public ?string $nombre = null,
        public ?string $descripcion = null,
        public ?string $estado = null
    ) {}
}
