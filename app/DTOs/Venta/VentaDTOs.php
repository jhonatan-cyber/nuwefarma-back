<?php

declare(strict_types=1);

namespace App\DTOs\Venta;

readonly class CreateVentaDTO
{
    public function __construct(
        public string $cliente_id,
        public string $usuario_id,
        public string $caja_id,
        public string $tipo_pago,
        public string $metodo_pago,
        public float $subtotal,
        public float $impuesto,
        public float $descuento = 0.0,
        public float $total,
        public float $pagado = 0.0,
        public float $saldo_pendiente = 0.0,
        public string $estado = 'pendiente',
        public ?string $observaciones = null,
        /** @var array<array<string, mixed> */
        public array $productos = []
    ) {}
}

readonly class UpdateVentaDTO
{
    public function __construct(
        public ?string $tipo_pago = null,
        public ?string $metodo_pago = null,
        public ?float $subtotal = null,
        public ?float $impuesto = null,
        public ?float $descuento = null,
        public ?float $total = null,
        public ?float $pagado = null,
        public ?float $saldo_pendiente = null,
        public ?string $estado = null,
        public ?string $observaciones = null
    ) {}
}

readonly class ListVentasDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $estado = null,
        public ?string $tipo_pago = null,
        public ?string $metodo_pago = null,
        public ?string $cliente_id = null,
        public ?string $usuario_id = null,
        public ?string $caja_id = null,
        public ?string $fecha_inicio = null,
        public ?string $fecha_fin = null,
        public ?float $total_min = null,
        public ?float $total_max = null,
        public ?bool $con_saldo = null,
        public ?string $sort = 'created_at',
        public string $direction = 'desc',
        public int $per_page = 15
    ) {}
}

readonly class CompleteVentaDTO
{
    public function __construct(
        public ?float $pagado = null
    ) {}
}
