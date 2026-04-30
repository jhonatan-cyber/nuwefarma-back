<?php

declare(strict_types=1);

namespace App\DTOs\Rol;

readonly class ListRolesDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $estado = null,
        public ?string $permiso = null,
        public ?bool $con_usuarios = null,
        public ?string $sort = 'nombre',
        public string $direction = 'asc',
        public int $per_page = 15
    ) {}
}
