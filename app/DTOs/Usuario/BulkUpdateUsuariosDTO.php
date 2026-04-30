<?php

declare(strict_types=1);

namespace App\DTOs\Usuario;

readonly class BulkUpdateUsuariosDTO
{
    public function __construct(
        /** @var array<string> */
        public array $ids,
        public string $estado
    ) {}
}
