<?php

declare(strict_types=1);

namespace App\DTOs\Cliente;

readonly class ListClientesDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $estado = null,
        public ?string $ci = null,
        public ?string $nit = null,
        public ?bool $con_deuda = null,
        public ?string $sort = 'nombre',
        public string $direction = 'asc',
        public int $per_page = 15
    ) {}
}
