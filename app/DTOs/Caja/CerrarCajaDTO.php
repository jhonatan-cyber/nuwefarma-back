<?php

declare(strict_types=1);

namespace App\DTOs\Caja;

readonly class CerrarCajaDTO
{
    public function __construct(
        public ?string $observaciones_cierre = null
    ) {}
}
