<?php

declare(strict_types=1);

namespace App\DTOs\Cliente;

readonly class BulkUpdateClientesDTO
{
    public function __construct(
        /** @var array<string> */
        public array $ids,
        public string $estado
    ) {}
}
