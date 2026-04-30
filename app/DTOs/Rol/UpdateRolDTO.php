<?php

declare(strict_types=1);

namespace App\DTOs\Rol;

readonly class UpdateRolDTO
{
    public function __construct(
        public ?string $nombre = null,
        public ?string $descripcion = null,
        /** @var array<string>|null */
        public ?array $permiso_id = null,
        public ?string $estado = null
    ) {}
}
