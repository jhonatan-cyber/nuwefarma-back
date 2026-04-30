<?php

declare(strict_types=1);

namespace App\DTOs\Sucursal;

readonly class ListSucursalesDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $estado = null,
        public ?string $ciudad = null,
        public ?string $departamento = null,
        public ?string $pais = null,
        public ?string $gerente_id = null,
        public ?int $capacidad_min = null,
        public ?int $capacidad_max = null,
        public ?string $sort = 'nombre',
        public string $direction = 'asc',
        public int $per_page = 15
    ) {}
}
