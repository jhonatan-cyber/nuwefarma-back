<?php

declare(strict_types=1);

namespace App\DTOs\Categoria;

readonly class ListCategoriasDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $estado = null,
        public ?string $sort = 'nombre',
        public string $direction = 'asc',
        public int $per_page = 15
    ) {}
}
