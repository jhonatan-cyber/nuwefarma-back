<?php

declare(strict_types=1);

namespace App\DTOs\Caja;

readonly class AbrirCajaDTO
{
    public function __construct(
        public ?float $saldo_inicial = null
    ) {}
}
