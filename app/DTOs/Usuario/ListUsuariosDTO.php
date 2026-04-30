<?php

declare(strict_types=1);

namespace App\DTOs\Usuario;

readonly class ListUsuariosDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $estado = null,
        public ?string $rol_id = null,
        public ?string $sucursal_id = null,
        public ?string $sort = 'nombre',
        public string $direction = 'asc',
        public int $per_page = 15
    ) {}
}
