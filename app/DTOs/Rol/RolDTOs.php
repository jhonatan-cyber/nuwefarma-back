<?php

declare(strict_types=1);

namespace App\DTOs\Rol;

readonly class CreateRolDTO
{
    public function __construct(
        public string $nombre,
        public string $descripcion,
        /** @var array<string> */
        public array $permiso_id = [],
        public string $estado = 'activo'
    ) {}
}

readonly class UpdateRolDTO
{
    public function __construct(
        public ?string $nombre = null,
        public ?string $descripcion = null,
        /** @var array<string>|null */
        public array|null $permiso_id = null,
        public ?string $estado = null
    ) {}
}

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

readonly class BulkUpdateRolesDTO
{
    public function __construct(
        /** @var array<string> */
        public array $ids,
        public string $estado
    ) {}
}
