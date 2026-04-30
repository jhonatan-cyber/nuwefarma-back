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
