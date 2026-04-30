<?php

declare(strict_types=1);

namespace App\DTOs\Proveedor;

readonly class BulkUpdateProveedoresDTO
{
    public function __construct(
        /** @var array<string> */
        public array $ids,
        public string $estado
    ) {}
}
