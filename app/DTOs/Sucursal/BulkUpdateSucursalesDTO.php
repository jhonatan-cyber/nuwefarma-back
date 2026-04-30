<?php

declare(strict_types=1);

namespace App\DTOs\Sucursal;

readonly class BulkUpdateSucursalesDTO
{
    public function __construct(
        /** @var array<string> */
        public array $ids,
        public string $estado
    ) {}
}
