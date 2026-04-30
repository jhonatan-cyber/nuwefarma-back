<?php

declare(strict_types=1);

namespace App\DTOs\Compra;

readonly class ListComprasDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $estado = null,
        public ?string $tipo_documento = null,
        public ?string $numero_documento = null,
        public ?string $proveedor_id = null,
        public ?string $usuario_id = null,
        public ?string $caja_id = null,
        public ?string $fecha_inicio = null,
        public ?string $fecha_fin = null,
        public ?string $created_at_inicio = null,
        public ?string $created_at_fin = null,
        public ?float $total_min = null,
        public ?float $total_max = null,
        public ?bool $con_saldo = null,
        public ?string $sort = 'created_at',
        public string $direction = 'desc',
        public int $per_page = 15
    ) {}
}
