<?php

declare(strict_types=1);

namespace App\DTOs\Caja;

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
