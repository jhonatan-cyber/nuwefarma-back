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
