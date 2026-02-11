<?php

declare(strict_types=1);

namespace App\DTOs\Categoria;

readonly class BulkUpdateCategoriasDTO
{
    public function __construct(
        /** @var array<string> */
        public array $ids,
        public string $estado
    ) {}
}
