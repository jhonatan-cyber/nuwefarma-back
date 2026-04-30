<?php

declare(strict_types=1);

namespace App\DTOs\Caja;

readonly class ListCajasDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $estado = null,
        public ?string $sucursal_id = null,
        public ?string $gerente_id = null,
        public ?float $saldo_min = null,
        public ?float $saldo_max = null,
        public ?bool $abiertas = null,
        public ?string $sort = 'nombre',
        public string $direction = 'asc',
        public int $per_page = 15
    ) {}
}
