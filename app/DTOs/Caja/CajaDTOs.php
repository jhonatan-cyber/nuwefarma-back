<?php

declare(strict_types=1);

namespace App\DTOs\Caja;

readonly class CreateCajaDTO
{
    public function __construct(
        public string $nombre,
        public string $sucursal_id,
        public string $gerente_id,
        public float $saldo_inicial,
        public float $saldo_actual = 0.0,
        public ?string $descripcion = null,
        public string $estado = 'cerrada'
    ) {}
}

readonly class UpdateCajaDTO
{
    public function __construct(
        public ?string $nombre = null,
        public ?string $sucursal_id = null,
        public ?string $gerente_id = null,
        public ?float $saldo_inicial = null,
        public ?string $descripcion = null,
        public ?string $estado = null
    ) {}
}

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

readonly class AbrirCajaDTO
{
    public function __construct(
        public ?float $saldo_inicial = null
    ) {}
}

readonly class CerrarCajaDTO
{
    public function __construct(
        public ?string $observaciones_cierre = null
    ) {}
}
