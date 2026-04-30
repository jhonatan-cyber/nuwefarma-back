<?php

declare(strict_types=1);

namespace App\DTOs\Rol;

readonly class BulkUpdateRolesDTO
{
    public function __construct(
        /** @var array<string> */
        public array $ids,
        public string $estado
    ) {}
}
