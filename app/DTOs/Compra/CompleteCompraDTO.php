<?php

declare(strict_types=1);

namespace App\DTOs\Compra;

readonly class CompleteCompraDTO
{
    public function __construct(
        public ?float $pagado = null
    ) {}
}
