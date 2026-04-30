<?php

declare(strict_types=1);

namespace App\DTOs\Compra;

readonly class CreateCompraDTO
{
    public function __construct(
        public string $proveedor_id,
        public string $usuario_id,
        public string $caja_id,
        public string $tipo_documento,
        public string $numero_documento,
        public string $fecha_documento,
        public float $subtotal,
        public float $impuesto,
        public float $descuento,
        public float $total,
        public float $pagado = 0.0,
        public float $saldo_pendiente = 0.0,
        public string $estado = 'pendiente',
        public ?string $observaciones = null,
        /** @var array<array<string, mixed> */
        public array $productos = []
    ) {}
}
